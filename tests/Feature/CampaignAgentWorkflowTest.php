<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\DB;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class CampaignAgentWorkflowTest extends TestCase
{
    use DatabaseTransactions;

    private User $owner;
    private User $agent;
    private int $contestId;
    private int $stationId;
    private int $assignmentId;

    protected function setUp(): void
    {
        parent::setUp();

        $this->stationId = (int) DB::table('electoral_register_snapshots')
            ->where('dataset_id', 1)
            ->value('polling_station_id');

        if ($this->stationId < 1) {
            throw new \RuntimeException(
                'Disposable dataset has no registered station.'
            );
        }

        $this->owner = $this->makeUser('owner');
        $this->agent = $this->makeUser('agent');

        $aspirantId = DB::table('aspirants')->insertGetId([
            'user_id' => $this->owner->id,
            'display_name' => 'Campaign Workflow Test',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $this->contestId = DB::table('aspirant_contests')->insertGetId([
            'contest_identity_key' => 'test-' . bin2hex(random_bytes(16)),
            'aspirant_id' => $aspirantId,
            'electoral_dataset_id' => 1,
            'election_year' => 2027,
            'office' => 'president',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $applicationId = DB::table('role_applications')->insertGetId([
            'aspirant_contest_id' => $this->contestId,
            'applicant_user_id' => $this->agent->id,
            'electoral_dataset_id' => 1,
            'requested_role' => 'polling_station_agent',
            'geographic_level' => 'polling_station',
            'polling_station_id' => $this->stationId,
            'status' => 'approved',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $this->assignmentId = DB::table('field_assignments')->insertGetId([
            'role_application_id' => $applicationId,
            'assigned_by' => $this->owner->id,
            'status' => 'assigned',
            'assigned_at' => now(),
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        DB::table('active_field_assignment_slots')->insert([
            'field_assignment_id' => $this->assignmentId,
            'aspirant_contest_id' => $this->contestId,
            'applicant_user_id' => $this->agent->id,
            'electoral_dataset_id' => 1,
            'requested_role' => 'polling_station_agent',
            'geographic_level' => 'polling_station',
            'geographic_id' => $this->stationId,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    public function test_assigned_agent_can_check_in(): void
    {
        $this->actingAs($this->agent)
            ->postJson(
                "/campaigns/{$this->contestId}/operations/station-reports",
                [
                    'polling_station_id' => $this->stationId,
                    'attendance_status' => 'checked_in',
                    'reported_turnout' => 150,
                    'notes' => 'Agent arrived at polling station.',
                ]
            )
            ->assertOk()
            ->assertJsonPath('status', 'success');

        $this->assertDatabaseHas('campaign_station_reports', [
            'aspirant_contest_id' => $this->contestId,
            'electoral_polling_station_id' => $this->stationId,
            'field_assignment_id' => $this->assignmentId,
            'attendance_status' => 'checked_in',
            'reported_turnout' => 150,
        ]);
    }

    public function test_assigned_agent_can_report_incident(): void
    {
        $this->actingAs($this->agent)
            ->postJson(
                "/campaigns/{$this->contestId}/operations/incidents",
                [
                    'polling_station_id' => $this->stationId,
                    'category' => 'voter_assistance',
                    'severity' => 'medium',
                    'description' => 'An incident requiring coordinator review.',
                ]
            )
            ->assertCreated()
            ->assertJsonPath('status', 'success');

        $this->assertDatabaseHas('campaign_incidents', [
            'aspirant_contest_id' => $this->contestId,
            'electoral_polling_station_id' => $this->stationId,
            'reported_by' => $this->agent->id,
            'severity' => 'medium',
            'status' => 'open',
        ]);
    }

    public function test_assigned_agent_can_submit_results(): void
    {
        $this->actingAs($this->agent)
            ->postJson(
                "/campaigns/{$this->contestId}/results",
                [
                    'polling_station_id' => $this->stationId,
                    'valid_votes' => 100,
                    'rejected_votes' => 3,
                    'candidate_votes' => [
                        'candidate_a' => 65,
                        'candidate_b' => 35,
                    ],
                ]
            )
            ->assertCreated()
            ->assertJsonPath('status', 'pending');

        $this->assertDatabaseHas('campaign_result_submissions', [
            'aspirant_contest_id' => $this->contestId,
            'electoral_polling_station_id' => $this->stationId,
            'submitted_by' => $this->agent->id,
            'valid_votes' => 100,
            'rejected_votes' => 3,
            'status' => 'pending',
        ]);
    }


    public function test_verification_requires_evidence(): void
    {
        $submissionId = $this->submitResult();

        $this->actingAs($this->owner)
            ->postJson(
                "/campaigns/{$this->contestId}/results/{$submissionId}/review",
                ['decision' => 'verified']
            )
            ->assertUnprocessable()
            ->assertJsonValidationErrors('evidence');

        $this->assertDatabaseHas('campaign_result_submissions', [
            'id' => $submissionId,
            'status' => 'pending',
        ]);

        $this->assertDatabaseMissing('campaign_verified_result_slots', [
            'campaign_result_submission_id' => $submissionId,
        ]);
    }

    public function test_evidence_backed_results_can_be_verified_and_tallied(): void
    {
        Storage::fake('local');

        $submissionId = $this->submitResult();
        $this->attachEvidence($submissionId);

        $this->actingAs($this->owner)
            ->postJson(
                "/campaigns/{$this->contestId}/results/{$submissionId}/review",
                ['decision' => 'verified']
            )
            ->assertOk()
            ->assertJsonPath('status', 'verified');

        $this->assertDatabaseHas('campaign_verified_result_slots', [
            'aspirant_contest_id' => $this->contestId,
            'electoral_polling_station_id' => $this->stationId,
            'campaign_result_submission_id' => $submissionId,
        ]);

        $this->actingAs($this->owner)
            ->getJson("/campaigns/{$this->contestId}/results/tally")
            ->assertOk()
            ->assertJsonPath('verified_submissions', 1)
            ->assertJsonPath('valid_votes', 100)
            ->assertJsonPath('rejected_votes', 3)
            ->assertJsonPath('candidate_totals.candidate_a', 65)
            ->assertJsonPath('candidate_totals.candidate_b', 35);
    }

    public function test_second_result_cannot_be_verified_for_same_station(): void
    {
        Storage::fake('local');

        $first = $this->submitResult();
        $second = $this->submitResult();

        $this->attachEvidence($first);
        $this->attachEvidence($second);

        $this->actingAs($this->owner)
            ->postJson(
                "/campaigns/{$this->contestId}/results/{$first}/review",
                ['decision' => 'verified']
            )
            ->assertOk();

        $this->actingAs($this->owner)
            ->postJson(
                "/campaigns/{$this->contestId}/results/{$second}/review",
                ['decision' => 'verified']
            )
            ->assertUnprocessable();

        $this->assertSame(
            1,
            DB::table('campaign_verified_result_slots')
                ->where('aspirant_contest_id', $this->contestId)
                ->where('electoral_polling_station_id', $this->stationId)
                ->count()
        );

        $this->assertDatabaseHas('campaign_result_submissions', [
            'id' => $second,
            'status' => 'pending',
        ]);
    }


    public function test_assigned_agent_can_upload_real_evidence(): void
    {
        Storage::fake('local');

        $submissionId = $this->submitResult();

        $file = UploadedFile::fake()->image('form34.jpg');

        $expectedHash = hash_file('sha256', $file->getRealPath());

        $response = $this->actingAs($this->agent)
            ->postJson(
                "/campaigns/{$this->contestId}/results/{$submissionId}/evidence",
                [
                    'file' => $file,
                    'form_type' => 'result_form',
                ]
            );

        $response->assertCreated()
            ->assertJsonPath('status', 'stored')
            ->assertJsonPath('sha256', $expectedHash);

        $evidenceId = (int) $response->json('evidence_id');

        $evidence = DB::table('campaign_result_evidence')
            ->where('id', $evidenceId)
            ->first();

        $this->assertNotNull($evidence);
        $this->assertSame($expectedHash, $evidence->file_hash);
        $this->assertSame($submissionId, (int) $evidence->campaign_result_submission_id);
        $this->assertSame($this->agent->id, (int) $evidence->uploaded_by);

        Storage::disk('local')->assertExists($evidence->file_path);

        $this->assertStringStartsWith(
            "campaign-results/{$this->contestId}/{$submissionId}/",
            $evidence->file_path
        );
    }

    public function test_unassigned_user_cannot_upload_evidence(): void
    {
        Storage::fake('local');

        $submissionId = $this->submitResult();
        $outsider = $this->makeUser('outsider');

        $this->actingAs($outsider)
            ->postJson(
                "/campaigns/{$this->contestId}/results/{$submissionId}/evidence",
                [
                    'file' => UploadedFile::fake()->image('unauthorized.jpg'),
                    'form_type' => 'result_form',
                ]
            )
            ->assertForbidden();

        $this->assertDatabaseMissing('campaign_result_evidence', [
            'campaign_result_submission_id' => $submissionId,
        ]);
    }


    public function test_agent_with_mismatched_dataset_cannot_upload_evidence(): void
    {
        Storage::fake('local');

        $submissionId = $this->submitResult();

        $contestDatasetId = (int) DB::table('aspirant_contests')
            ->where('id', $this->contestId)
            ->value('electoral_dataset_id');

        $otherDatasetId = DB::table('electoral_datasets')
            ->insertGetId([
                'name' => 'KuraGuard Synthetic Authorization Test Dataset',
                'version' => 'test-only-' . bin2hex(random_bytes(8)),
                'source_organization' => 'KuraGuard Automated Tests',
                'reference_year' => 2026,
                'status' => 'draft',
                'notes' => 'Synthetic fixture for authorization testing only.',
                'created_at' => now(),
                'updated_at' => now(),
            ]);

        $this->assertNotEquals(
            $contestDatasetId,
            $otherDatasetId,
            'Synthetic dataset must differ from campaign dataset.'
        );

        DB::table('active_field_assignment_slots')
            ->where('aspirant_contest_id', $this->contestId)
            ->where('applicant_user_id', $this->agent->id)
            ->update([
                'electoral_dataset_id' => $otherDatasetId,
                'updated_at' => now(),
            ]);

        $this->actingAs($this->agent)
            ->postJson(
                "/campaigns/{$this->contestId}/results/{$submissionId}/evidence",
                [
                    'file' => UploadedFile::fake()->image('wrong-dataset.jpg'),
                    'form_type' => 'result_form',
                ]
            )
            ->assertForbidden();

        $this->assertDatabaseMissing('campaign_result_evidence', [
            'campaign_result_submission_id' => $submissionId,
        ]);
    }

    private function submitResult(): int
    {
        $response = $this->actingAs($this->agent)
            ->postJson(
                "/campaigns/{$this->contestId}/results",
                [
                    'polling_station_id' => $this->stationId,
                    'valid_votes' => 100,
                    'rejected_votes' => 3,
                    'candidate_votes' => [
                        'candidate_a' => 65,
                        'candidate_b' => 35,
                    ],
                ]
            );

        $response->assertCreated();

        return (int) $response->json('submission_id');
    }

    private function attachEvidence(int $submissionId): void
    {
        DB::table('campaign_result_evidence')->insert([
            'campaign_result_submission_id' => $submissionId,
            'uploaded_by' => $this->agent->id,
            'file_path' => 'campaign-results/test-evidence.jpg',
            'file_hash' => hash('sha256', 'test-evidence'),
            'form_type' => 'result_form',
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    private function makeUser(string $suffix): User
    {
        $id = DB::table('users')->insertGetId([
            'name' => 'Campaign Workflow ' . $suffix,
            'email' => 'campaign-workflow-' . $suffix . '-'
                . bin2hex(random_bytes(6)) . '@example.test',
            'password' => bcrypt('test-password'),
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        return User::findOrFail($id);
    }
}
