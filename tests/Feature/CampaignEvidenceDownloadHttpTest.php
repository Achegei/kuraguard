<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class CampaignEvidenceDownloadHttpTest extends TestCase
{
    use DatabaseTransactions;

    private User $owner;
    private User $otherOwner;
    private User $outsider;

    private int $contestId;
    private int $otherContestId;
    private int $stationId;
    private int $submissionId;
    private int $evidenceId;

    private string $content = 'KuraGuard test evidence document';

    protected function setUp(): void
    {
        parent::setUp();

        Storage::fake('local');

        $this->owner = $this->makeUser('owner');
        $this->otherOwner = $this->makeUser('other-owner');
        $this->outsider = $this->makeUser('outsider');

        $this->contestId = $this->makeContest($this->owner);
        $this->otherContestId = $this->makeContest($this->otherOwner);

        $this->stationId = (int) DB::table('electoral_register_snapshots')
            ->where('dataset_id', 1)
            ->value('polling_station_id');

        if ($this->stationId < 1) {
            throw new \RuntimeException(
                'Disposable electoral dataset has no registered station.'
            );
        }

        $this->submissionId = $this->makeSubmission(
            $this->contestId,
            $this->owner
        );

        $this->evidenceId = $this->makeEvidence(
            $this->submissionId,
            $this->contestId,
            $this->content
        );
    }

    public function test_guest_cannot_download_evidence(): void
    {
        $this->getJson($this->endpoint())
            ->assertUnauthorized();
    }

    public function test_outsider_cannot_download_evidence(): void
    {
        $this->actingAs($this->outsider)
            ->getJson($this->endpoint())
            ->assertForbidden();
    }

    public function test_owner_can_download_valid_evidence(): void
    {
        $this->actingAs($this->owner)
            ->get($this->endpoint())
            ->assertOk()
            ->assertDownload(
                'campaign-evidence-'.$this->evidenceId.'.jpg'
            );
    }

    public function test_other_campaign_owner_cannot_download_evidence(): void
    {
        $this->actingAs($this->otherOwner)
            ->getJson($this->endpoint())
            ->assertForbidden();
    }

    public function test_evidence_cannot_be_accessed_through_another_campaign(): void
    {
        $this->actingAs($this->otherOwner)
            ->getJson($this->endpoint(
                $this->otherContestId,
                $this->submissionId,
                $this->evidenceId
            ))
            ->assertNotFound();
    }

    public function test_evidence_cannot_be_accessed_through_another_submission(): void
    {
        $anotherSubmission = $this->makeSubmission(
            $this->contestId,
            $this->owner
        );

        $this->actingAs($this->owner)
            ->getJson($this->endpoint(
                $this->contestId,
                $anotherSubmission,
                $this->evidenceId
            ))
            ->assertNotFound();
    }

    public function test_missing_file_returns_not_found(): void
    {
        $path = DB::table('campaign_result_evidence')
            ->where('id', $this->evidenceId)
            ->value('file_path');

        Storage::disk('local')->delete($path);

        $this->actingAs($this->owner)
            ->getJson($this->endpoint())
            ->assertNotFound();
    }

    public function test_modified_file_fails_integrity_verification(): void
    {
        $path = DB::table('campaign_result_evidence')
            ->where('id', $this->evidenceId)
            ->value('file_path');

        Storage::disk('local')->put(
            $path,
            'Evidence was modified after upload'
        );

        $this->actingAs($this->owner)
            ->getJson($this->endpoint())
            ->assertStatus(409);
    }

    public function test_path_traversal_is_rejected(): void
    {
        DB::table('campaign_result_evidence')
            ->where('id', $this->evidenceId)
            ->update([
                'file_path' => 'campaign-results/'
                    .$this->contestId.'/'
                    .$this->submissionId.'/../../other-file.jpg',
            ]);

        $this->actingAs($this->owner)
            ->getJson($this->endpoint())
            ->assertNotFound();
    }

    public function test_successful_download_creates_audit_record(): void
    {
        $this->actingAs($this->owner)
            ->get($this->endpoint())
            ->assertOk();

        $this->assertDatabaseHas('audit_logs', [
            'user_id' => $this->owner->id,
            'action' => 'campaign.result.evidence_downloaded',
            'entity_type' => 'campaign_result_evidence',
            'entity_id' => $this->evidenceId,
        ]);
    }

    public function test_failed_integrity_check_does_not_create_download_audit(): void
    {
        $path = DB::table('campaign_result_evidence')
            ->where('id', $this->evidenceId)
            ->value('file_path');

        Storage::disk('local')->put($path, 'Tampered document');

        $this->actingAs($this->owner)
            ->getJson($this->endpoint())
            ->assertStatus(409);

        $this->assertDatabaseMissing('audit_logs', [
            'action' => 'campaign.result.evidence_downloaded',
            'entity_type' => 'campaign_result_evidence',
            'entity_id' => $this->evidenceId,
        ]);
    }

    private function endpoint(
        ?int $contestId = null,
        ?int $submissionId = null,
        ?int $evidenceId = null
    ): string {
        return route('campaign.results.evidence.download', [
            'contestId' => $contestId ?? $this->contestId,
            'submissionId' => $submissionId ?? $this->submissionId,
            'evidenceId' => $evidenceId ?? $this->evidenceId,
        ]);
    }

    private function makeUser(string $suffix): User
    {
        $id = DB::table('users')->insertGetId([
            'name' => 'Evidence Download '.$suffix,
            'email' => 'evidence-download-'.$suffix.'@example.test',
            'password' => bcrypt('test-password'),
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        return User::findOrFail($id);
    }

    private function makeContest(User $owner): int
    {
        $aspirantId = DB::table('aspirants')->insertGetId([
            'user_id' => $owner->id,
            'display_name' => 'Evidence Download Test',
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

    private function makeSubmission(int $contestId, User $submitter): int
    {
        return DB::table('campaign_result_submissions')->insertGetId([
            'aspirant_contest_id' => $contestId,
            'electoral_polling_station_id' => $this->stationId,
            'submitted_by' => $submitter->id,
            'valid_votes' => 100,
            'rejected_votes' => 2,
            'candidate_votes' => json_encode([
                'candidate_a' => 60,
                'candidate_b' => 40,
            ]),
            'status' => 'pending',
            'submitted_at' => now(),
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    private function makeEvidence(
        int $submissionId,
        int $contestId,
        string $content
    ): int {
        $path = "campaign-results/{$contestId}/{$submissionId}/form.jpg";

        Storage::disk('local')->put($path, $content);

        return DB::table('campaign_result_evidence')->insertGetId([
            'campaign_result_submission_id' => $submissionId,
            'uploaded_by' => $this->owner->id,
            'file_path' => $path,
            'file_hash' => hash('sha256', $content),
            'form_type' => 'result_form',
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }
}
