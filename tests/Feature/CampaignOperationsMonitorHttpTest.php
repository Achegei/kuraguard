<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class CampaignOperationsMonitorHttpTest extends TestCase
{
    use DatabaseTransactions;

    private User $owner;
    private User $outsider;
    private User $otherOwner;
    private int $contestId;
    private int $otherContestId;

    protected function setUp(): void
    {
        parent::setUp();

        Storage::fake('local');

        $this->owner = $this->makeUser('owner');
        $this->outsider = $this->makeUser('outsider');
        $this->otherOwner = $this->makeUser('other-owner');

        $this->contestId = $this->makeContest(
            $this->owner,
            'Monitor Primary Aspirant'
        );

        $this->otherContestId = $this->makeContest(
            $this->otherOwner,
            'Monitor Other Aspirant'
        );
    }

    public function test_guest_cannot_access_monitor(): void
    {
        $this->getJson($this->endpoint())
            ->assertUnauthorized();
    }

    public function test_owner_can_view_monitor(): void
    {
        $this->actingAs($this->owner)
            ->get($this->endpoint())
            ->assertOk()
            ->assertViewIs('campaigns.operations-monitor')
            ->assertSee('Campaign Command Centre')
            ->assertSee('Monitor Primary Aspirant');
    }

    public function test_outsider_is_forbidden(): void
    {
        $this->actingAs($this->outsider)
            ->get($this->endpoint())
            ->assertForbidden();
    }

    public function test_owner_cannot_view_other_campaign(): void
    {
        $this->actingAs($this->owner)
            ->get($this->endpoint($this->otherContestId))
            ->assertForbidden();
    }

    public function test_empty_monitor_renders(): void
    {
        $this->actingAs($this->owner)
            ->get($this->endpoint())
            ->assertOk()
            ->assertSee('No polling station reports received yet.')
            ->assertSee('No incidents have been reported.')
            ->assertSee('No station results have been submitted.')
            ->assertViewHas('statistics', function ($statistics) {
                return count($statistics) === 5
                    && array_sum(array_map('intval', $statistics)) === 0;
            });
    }

    public function test_incidents_are_scoped_to_campaign(): void
    {
        $stationId = $this->registeredStationId();

        $this->makeIncident(
            $this->contestId,
            $stationId,
            'Visible campaign incident'
        );

        $this->makeIncident(
            $this->otherContestId,
            $stationId,
            'Confidential other campaign incident'
        );

        $this->actingAs($this->owner)
            ->get($this->endpoint())
            ->assertOk()
            ->assertSee('Visible campaign incident')
            ->assertDontSee('Confidential other campaign incident')
            ->assertViewHas('statistics', function ($statistics) {
                return $statistics['open_incidents'] === 1;
            });
    }

    public function test_result_submissions_are_scoped_to_campaign(): void
    {
        $stationId = $this->registeredStationId();

        $this->makeResult(
            $this->contestId,
            $stationId,
            125
        );

        $this->makeResult(
            $this->otherContestId,
            $stationId,
            98765
        );

        $this->actingAs($this->owner)
            ->get($this->endpoint())
            ->assertOk()
            ->assertViewHas('resultSubmissions', function ($submissions) {
                return $submissions->count() === 1
                    && (int) $submissions->first()->valid_votes === 125;
            })
            ->assertViewHas('statistics', function ($statistics) {
                return $statistics['pending_results'] === 1;
            });
    }


    public function test_station_reports_are_scoped_to_campaign(): void
    {
        $stationId = $this->registeredStationId();

        $this->makeStationReport(
            $this->contestId,
            $stationId,
            123
        );

        $this->makeStationReport(
            $this->otherContestId,
            $stationId,
            98765
        );

        $this->actingAs($this->owner)
            ->get($this->endpoint())
            ->assertOk()
            ->assertSee('123')
            ->assertDontSee('98765')
            ->assertViewHas('stationReports', function ($reports) {
                return $reports->count() === 1
                    && (int) $reports->first()->reported_turnout === 123;
            })
            ->assertViewHas('statistics', function ($statistics) {
                return $statistics['station_reports'] === 1
                    && $statistics['checked_in_stations'] === 1;
            });
    }

    public function test_evidence_counts_are_scoped_to_submissions(): void
    {
        $stationId = $this->registeredStationId();

        $ownSubmissionId = $this->makeResultWithId(
            $this->contestId,
            $stationId,
            321
        );

        $otherSubmissionId = $this->makeResultWithId(
            $this->otherContestId,
            $stationId,
            654
        );

        $this->makeEvidence($ownSubmissionId, 'own-evidence-1');
        $this->makeEvidence($ownSubmissionId, 'own-evidence-2');
        $this->makeEvidence($otherSubmissionId, 'other-evidence-1');

        $this->actingAs($this->owner)
            ->get($this->endpoint())
            ->assertOk()
            ->assertViewHas('resultSubmissions', function ($submissions) use (
                $ownSubmissionId
            ) {
                return $submissions->count() === 1
                    && (int) $submissions->first()->id === $ownSubmissionId
                    && (int) $submissions->first()->evidence_count === 2;
            });
    }


    public function test_owner_sees_campaign_scoped_evidence_download_links(): void
    {
        $stationId = $this->registeredStationId();

        $submissionId = $this->makeResultWithId(
            $this->contestId,
            $stationId,
            321
        );

        $this->makeEvidence($submissionId, 'visible-evidence');

        $evidenceId = (int) DB::table('campaign_result_evidence')
            ->where('campaign_result_submission_id', $submissionId)
            ->value('id');

        $url = route('campaign.results.evidence.download', [
            'contestId' => $this->contestId,
            'submissionId' => $submissionId,
            'evidenceId' => $evidenceId,
        ]);

        $this->actingAs($this->owner)
            ->get($this->endpoint())
            ->assertOk()
            ->assertSee($url)
            ->assertSee('Download Evidence #'.$evidenceId);
    }

    public function test_other_campaign_evidence_links_are_not_visible(): void
    {
        $stationId = $this->registeredStationId();

        $ownSubmissionId = $this->makeResultWithId(
            $this->contestId,
            $stationId,
            321
        );

        $otherSubmissionId = $this->makeResultWithId(
            $this->otherContestId,
            $stationId,
            654
        );

        $this->makeEvidence($ownSubmissionId, 'visible-own-evidence');
        $this->makeEvidence($otherSubmissionId, 'hidden-other-evidence');

        $otherEvidenceId = (int) DB::table('campaign_result_evidence')
            ->where('campaign_result_submission_id', $otherSubmissionId)
            ->value('id');

        $otherUrl = route('campaign.results.evidence.download', [
            'contestId' => $this->otherContestId,
            'submissionId' => $otherSubmissionId,
            'evidenceId' => $otherEvidenceId,
        ]);

        $this->actingAs($this->owner)
            ->get($this->endpoint())
            ->assertOk()
            ->assertDontSee($otherUrl)
            ->assertDontSee('Download Evidence #'.$otherEvidenceId);
    }

    public function test_submission_without_evidence_has_no_download_link(): void
    {
        $this->makeResultWithId(
            $this->contestId,
            $this->registeredStationId(),
            321
        );

        $this->actingAs($this->owner)
            ->get($this->endpoint())
            ->assertOk()
            ->assertSee('0 file(s)')
            ->assertDontSee('Download Evidence #');
    }

    public function test_active_non_reviewer_can_view_but_not_download_evidence(): void
    {
        $member = $this->makeUser('evidence-viewer');

        $aspirantId = (int) DB::table('aspirant_contests')
            ->where('id', $this->contestId)
            ->value('aspirant_id');

        DB::table('aspirant_memberships')->insert([
            'aspirant_id' => $aspirantId,
            'user_id' => $member->id,
            'role' => 'campaign_observer',
            'status' => 'active',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $submissionId = $this->makeResultWithId(
            $this->contestId,
            $this->registeredStationId(),
            321
        );

        $this->makeEvidence($submissionId, 'restricted-evidence');

        $evidenceId = (int) DB::table('campaign_result_evidence')
            ->where('campaign_result_submission_id', $submissionId)
            ->value('id');

        $url = route('campaign.results.evidence.download', [
            'contestId' => $this->contestId,
            'submissionId' => $submissionId,
            'evidenceId' => $evidenceId,
        ]);

        $this->actingAs($member)
            ->get($this->endpoint())
            ->assertOk()
            ->assertSee('1 file(s)')
            ->assertDontSee('Download Evidence #')
            ->assertDontSee($url);

        $this->actingAs($member)
            ->get($url)
            ->assertForbidden();
    }


    public function test_browser_review_owner_can_verify_with_evidence(): void
    {
        $submissionId = $this->makeResultWithId(
            $this->contestId,
            $this->registeredStationId(),
            321
        );

        $this->makeEvidence($submissionId, 'web-verify-evidence');

        $this->actingAs($this->owner)
            ->post($this->reviewEndpoint($submissionId), [
                'decision' => 'verified',
            ])
            ->assertRedirect($this->endpoint())
            ->assertSessionHas('status');

        $this->assertDatabaseHas('campaign_result_submissions', [
            'id' => $submissionId,
            'aspirant_contest_id' => $this->contestId,
            'status' => 'verified',
            'verified_by' => $this->owner->id,
        ]);

        $this->assertDatabaseHas('campaign_verified_result_slots', [
            'aspirant_contest_id' => $this->contestId,
            'campaign_result_submission_id' => $submissionId,
        ]);

        $this->assertDatabaseHas('audit_logs', [
            'action' => 'campaign.result.verified',
            'entity_id' => $submissionId,
        ]);
    }

    public function test_browser_review_cannot_verify_missing_evidence_file(): void
    {
        $submissionId = $this->makeResultWithId(
            $this->contestId,
            $this->registeredStationId(),
            321
        );

        $this->makeEvidence($submissionId, 'missing-file');

        $path = DB::table('campaign_result_evidence')
            ->where('campaign_result_submission_id', $submissionId)
            ->value('file_path');

        Storage::disk('local')->delete($path);

        $this->assertEvidenceVerificationBlocked($submissionId);
    }

    public function test_browser_review_cannot_verify_tampered_evidence(): void
    {
        $submissionId = $this->makeResultWithId(
            $this->contestId,
            $this->registeredStationId(),
            321
        );

        $this->makeEvidence($submissionId, 'tampered-file');

        $path = DB::table('campaign_result_evidence')
            ->where('campaign_result_submission_id', $submissionId)
            ->value('file_path');

        Storage::disk('local')->put(
            $path,
            'Modified evidence after original upload'
        );

        $this->assertEvidenceVerificationBlocked($submissionId);
    }

    public function test_browser_review_cannot_verify_invalid_evidence_path(): void
    {
        $submissionId = $this->makeResultWithId(
            $this->contestId,
            $this->registeredStationId(),
            321
        );

        $this->makeEvidence($submissionId, 'invalid-path');

        DB::table('campaign_result_evidence')
            ->where('campaign_result_submission_id', $submissionId)
            ->update([
                'file_path' => 'campaign-results/other-campaign/file.jpg',
            ]);

        $this->assertEvidenceVerificationBlocked($submissionId);
    }

    public function test_browser_review_cannot_verify_when_one_evidence_file_is_tampered(): void
    {
        $submissionId = $this->makeResultWithId(
            $this->contestId,
            $this->registeredStationId(),
            321
        );

        $this->makeEvidence($submissionId, 'valid-first-file');
        $this->makeEvidence($submissionId, 'tampered-second-file');

        $path = DB::table('campaign_result_evidence')
            ->where('campaign_result_submission_id', $submissionId)
            ->where(
                'file_path',
                'like',
                '%tampered-second-file.jpg'
            )
            ->value('file_path');

        Storage::disk('local')->put(
            $path,
            'Corrupted second evidence file'
        );

        $this->assertEvidenceVerificationBlocked($submissionId);
    }

    public function test_browser_review_owner_can_reject_with_reason(): void
    {
        $submissionId = $this->makeResultWithId(
            $this->contestId,
            $this->registeredStationId(),
            321
        );

        $reason = 'The submitted polling station figures require correction.';

        $this->actingAs($this->owner)
            ->post($this->reviewEndpoint($submissionId), [
                'decision' => 'rejected',
                'reason' => $reason,
            ])
            ->assertRedirect($this->endpoint())
            ->assertSessionHas('status');

        $this->assertDatabaseHas('campaign_result_submissions', [
            'id' => $submissionId,
            'status' => 'rejected',
            'notes' => $reason,
        ]);

        $this->assertDatabaseHas('audit_logs', [
            'action' => 'campaign.result.rejected',
            'entity_id' => $submissionId,
        ]);
    }

    public function test_browser_review_requires_rejection_reason(): void
    {
        $submissionId = $this->makeResultWithId(
            $this->contestId,
            $this->registeredStationId(),
            321
        );

        $this->actingAs($this->owner)
            ->from($this->endpoint())
            ->post($this->reviewEndpoint($submissionId), [
                'decision' => 'rejected',
            ])
            ->assertRedirect($this->endpoint())
            ->assertSessionHasErrors('reason');

        $this->assertDatabaseHas('campaign_result_submissions', [
            'id' => $submissionId,
            'status' => 'pending',
        ]);
    }

    public function test_browser_review_rejects_invalid_decision(): void
    {
        $submissionId = $this->makeResultWithId(
            $this->contestId,
            $this->registeredStationId(),
            321
        );

        $this->actingAs($this->owner)
            ->from($this->endpoint())
            ->post($this->reviewEndpoint($submissionId), [
                'decision' => 'approved',
            ])
            ->assertRedirect($this->endpoint())
            ->assertSessionHasErrors('decision');

        $this->assertDatabaseHas('campaign_result_submissions', [
            'id' => $submissionId,
            'status' => 'pending',
        ]);
    }

    public function test_browser_review_cannot_verify_without_evidence(): void
    {
        $submissionId = $this->makeResultWithId(
            $this->contestId,
            $this->registeredStationId(),
            321
        );

        $this->actingAs($this->owner)
            ->from($this->endpoint())
            ->post($this->reviewEndpoint($submissionId), [
                'decision' => 'verified',
            ])
            ->assertRedirect($this->endpoint())
            ->assertSessionHasErrors('evidence');

        $this->assertDatabaseHas('campaign_result_submissions', [
            'id' => $submissionId,
            'status' => 'pending',
        ]);

        $this->assertDatabaseMissing('campaign_verified_result_slots', [
            'campaign_result_submission_id' => $submissionId,
        ]);
    }

    public function test_browser_review_cannot_review_completed_submission_again(): void
    {
        $submissionId = $this->makeResultWithId(
            $this->contestId,
            $this->registeredStationId(),
            321
        );

        $this->actingAs($this->owner)
            ->post($this->reviewEndpoint($submissionId), [
                'decision' => 'rejected',
                'reason' => 'The uploaded figures are inconsistent.',
            ])
            ->assertRedirect($this->endpoint());

        $this->actingAs($this->owner)
            ->from($this->endpoint())
            ->post($this->reviewEndpoint($submissionId), [
                'decision' => 'rejected',
                'reason' => 'Attempting to review the result again.',
            ])
            ->assertRedirect($this->endpoint())
            ->assertSessionHasErrors('status');

        $this->assertDatabaseHas('campaign_result_submissions', [
            'id' => $submissionId,
            'status' => 'rejected',
            'notes' => 'The uploaded figures are inconsistent.',
        ]);
    }

    public function test_browser_review_outsider_is_forbidden(): void
    {
        $submissionId = $this->makeResultWithId(
            $this->contestId,
            $this->registeredStationId(),
            321
        );

        $this->actingAs($this->outsider)
            ->post($this->reviewEndpoint($submissionId), [
                'decision' => 'rejected',
                'reason' => 'Unauthorized review attempt.',
            ])
            ->assertForbidden();

        $this->assertDatabaseHas('campaign_result_submissions', [
            'id' => $submissionId,
            'status' => 'pending',
        ]);
    }

    public function test_browser_review_observer_is_forbidden(): void
    {
        $observer = $this->makeUser('web-review-observer');

        $aspirantId = (int) DB::table('aspirant_contests')
            ->where('id', $this->contestId)
            ->value('aspirant_id');

        DB::table('aspirant_memberships')->insert([
            'aspirant_id' => $aspirantId,
            'user_id' => $observer->id,
            'role' => 'campaign_observer',
            'status' => 'active',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $submissionId = $this->makeResultWithId(
            $this->contestId,
            $this->registeredStationId(),
            321
        );

        $this->actingAs($observer)
            ->post($this->reviewEndpoint($submissionId), [
                'decision' => 'rejected',
                'reason' => 'Observer must not review results.',
            ])
            ->assertForbidden();

        $this->assertDatabaseHas('campaign_result_submissions', [
            'id' => $submissionId,
            'status' => 'pending',
        ]);
    }

    public function test_browser_review_cannot_modify_other_campaign(): void
    {
        $submissionId = $this->makeResultWithId(
            $this->otherContestId,
            $this->registeredStationId(),
            321
        );

        $this->actingAs($this->owner)
            ->post(
                $this->reviewEndpoint($submissionId, $this->otherContestId),
                [
                    'decision' => 'rejected',
                    'reason' => 'Cross-campaign review must be forbidden.',
                ]
            )
            ->assertForbidden();

        $this->actingAs($this->owner)
            ->post($this->reviewEndpoint($submissionId), [
                'decision' => 'rejected',
                'reason' => 'Cross-campaign submission must not be found.',
            ])
            ->assertNotFound();

        $this->assertDatabaseHas('campaign_result_submissions', [
            'id' => $submissionId,
            'aspirant_contest_id' => $this->otherContestId,
            'status' => 'pending',
        ]);
    }

    public function test_browser_review_guest_cannot_submit_decision(): void
    {
        $submissionId = $this->makeResultWithId(
            $this->contestId,
            $this->registeredStationId(),
            321
        );

        $this->postJson($this->reviewEndpoint($submissionId), [
            'decision' => 'verified',
        ])->assertUnauthorized();

        $this->assertDatabaseHas('campaign_result_submissions', [
            'id' => $submissionId,
            'status' => 'pending',
        ]);
    }

    private function reviewEndpoint(
        int $submissionId,
        ?int $contestId = null
    ): string {
        return route('campaign.results.web-review', [
            'contestId' => $contestId ?? $this->contestId,
            'submissionId' => $submissionId,
        ]);
    }

    private function makeStationReport(
        int $contestId,
        int $stationId,
        int $turnout
    ): void {
        $applicationId = DB::table('role_applications')->insertGetId([
            'aspirant_contest_id' => $contestId,
            'applicant_user_id' => $contestId === $this->contestId
                ? $this->owner->id
                : $this->otherOwner->id,
            'electoral_dataset_id' => 1,
            'requested_role' => 'polling_station_agent',
            'geographic_level' => 'polling_station',
            'polling_station_id' => $stationId,
            'status' => 'approved',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $assignmentId = DB::table('field_assignments')->insertGetId([
            'role_application_id' => $applicationId,
            'assigned_by' => $contestId === $this->contestId
                ? $this->owner->id
                : $this->otherOwner->id,
            'status' => 'assigned',
            'assigned_at' => now(),
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        DB::table('campaign_station_reports')->insert([
            'aspirant_contest_id' => $contestId,
            'electoral_polling_station_id' => $stationId,
            'field_assignment_id' => $assignmentId,
            'attendance_status' => 'checked_in',
            'checked_in_at' => now(),
            'reported_turnout' => $turnout,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    private function makeResultWithId(
        int $contestId,
        int $stationId,
        int $votes
    ): int {
        return DB::table('campaign_result_submissions')->insertGetId([
            'aspirant_contest_id' => $contestId,
            'electoral_polling_station_id' => $stationId,
            'submitted_by' => $this->owner->id,
            'valid_votes' => $votes,
            'rejected_votes' => 0,
            'candidate_votes' => json_encode([
                'candidate_a' => $votes,
            ]),
            'status' => 'pending',
            'submitted_at' => now(),
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    private function assertEvidenceVerificationBlocked(int $submissionId): void
    {
        $this->actingAs($this->owner)
            ->from($this->endpoint())
            ->post($this->reviewEndpoint($submissionId), [
                'decision' => 'verified',
            ])
            ->assertRedirect($this->endpoint())
            ->assertSessionHasErrors('evidence');

        $this->assertDatabaseHas('campaign_result_submissions', [
            'id' => $submissionId,
            'status' => 'pending',
        ]);

        $this->assertDatabaseMissing('campaign_verified_result_slots', [
            'campaign_result_submission_id' => $submissionId,
        ]);

        $this->assertDatabaseMissing('audit_logs', [
            'action' => 'campaign.result.verified',
            'entity_id' => $submissionId,
        ]);
    }

    private function makeEvidence(
        int $submissionId,
        string $identifier
    ): void {
        $contestId = (int) DB::table('campaign_result_submissions')
            ->where('id', $submissionId)
            ->value('aspirant_contest_id');

        if ($contestId < 1) {
            throw new \RuntimeException(
                'Evidence fixture requires an existing submission.'
            );
        }

        $path = "campaign-results/{$contestId}/{$submissionId}/"
            .$identifier.'.jpg';

        $content = 'KuraGuard test evidence: '.$identifier;

        if (! Storage::disk('local')->put($path, $content)) {
            throw new \RuntimeException(
                'Failed to create test evidence file.'
            );
        }

        DB::table('campaign_result_evidence')->insert([
            'campaign_result_submission_id' => $submissionId,
            'uploaded_by' => $this->owner->id,
            'file_path' => $path,
            'file_hash' => hash('sha256', $content),
            'form_type' => 'result_form',
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    private function endpoint(?int $contestId = null): string
    {
        return route('campaign.operations.monitor', [
            'contestId' => $contestId ?? $this->contestId,
        ]);
    }

    private function registeredStationId(): int
    {
        $stationId = (int) DB::table('electoral_register_snapshots')
            ->where('dataset_id', 1)
            ->value('polling_station_id');

        if ($stationId < 1) {
            throw new \RuntimeException(
                'Disposable electoral dataset has no polling station.'
            );
        }

        return $stationId;
    }

    private function makeUser(string $suffix): User
    {
        $id = DB::table('users')->insertGetId([
            'name' => 'Monitor HTTP '.$suffix,
            'email' => 'monitor-http-'.$suffix.'@example.test',
            'password' => bcrypt('test-password'),
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        return User::findOrFail($id);
    }

    private function makeContest(
        User $owner,
        string $displayName
    ): int {
        $aspirantId = DB::table('aspirants')->insertGetId([
            'user_id' => $owner->id,
            'display_name' => $displayName,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        return DB::table('aspirant_contests')->insertGetId([
            'contest_identity_key' => 'test-'.bin2hex(random_bytes(16)),
            'aspirant_id' => $aspirantId,
            'electoral_dataset_id' => 1,
            'election_year' => 2027,
            'office' => 'president',
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    private function makeIncident(
        int $contestId,
        int $stationId,
        string $description
    ): void {
        DB::table('campaign_incidents')->insert([
            'aspirant_contest_id' => $contestId,
            'electoral_polling_station_id' => $stationId,
            'reported_by' => $this->owner->id,
            'category' => 'monitor_test',
            'severity' => 'high',
            'description' => $description,
            'status' => 'open',
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    private function makeResult(
        int $contestId,
        int $stationId,
        int $votes
    ): void {
        DB::table('campaign_result_submissions')->insert([
            'aspirant_contest_id' => $contestId,
            'electoral_polling_station_id' => $stationId,
            'submitted_by' => $this->owner->id,
            'valid_votes' => $votes,
            'rejected_votes' => 0,
            'candidate_votes' => json_encode([
                'candidate_a' => $votes,
            ]),
            'status' => 'pending',
            'submitted_at' => now(),
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }
}
