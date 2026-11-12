<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class CampaignAgentAuthorizationTest extends TestCase
{
    use DatabaseTransactions;

    private User $owner;
    private User $outsider;
    private int $contestId;
    private int $stationId;

    protected function setUp(): void
    {
        parent::setUp();

        $this->stationId = (int) DB::table(
            'electoral_register_snapshots'
        )
            ->where('dataset_id', 1)
            ->value('polling_station_id');

        if ($this->stationId < 1) {
            throw new \RuntimeException(
                'Test dataset has no registered polling station.'
            );
        }

        $this->owner = $this->makeUser('owner');
        $this->outsider = $this->makeUser('outsider');

        $aspirantId = DB::table('aspirants')->insertGetId([
            'user_id' => $this->owner->id,
            'display_name' => 'Campaign Agent Security Test',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $this->contestId = DB::table(
            'aspirant_contests'
        )->insertGetId([
            'contest_identity_key' =>
                'test-' . bin2hex(random_bytes(16)),
            'aspirant_id' => $aspirantId,
            'electoral_dataset_id' => 1,
            'election_year' => 2027,
            'office' => 'president',
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    public function test_unassigned_user_cannot_submit_station_report(): void
    {
        $this->actingAs($this->outsider)
            ->postJson(
                "/campaigns/{$this->contestId}/operations/station-reports",
                [
                    'polling_station_id' => $this->stationId,
                    'attendance_status' => 'checked_in',
                    'reported_turnout' => 100,
                ]
            )
            ->assertForbidden();

        $this->assertDatabaseMissing(
            'campaign_station_reports',
            [
                'aspirant_contest_id' => $this->contestId,
                'electoral_polling_station_id' => $this->stationId,
            ]
        );
    }

    public function test_unassigned_user_cannot_submit_results(): void
    {
        $this->actingAs($this->outsider)
            ->postJson(
                "/campaigns/{$this->contestId}/results",
                [
                    'polling_station_id' => $this->stationId,
                    'valid_votes' => 100,
                    'rejected_votes' => 2,
                    'candidate_votes' => [
                        'candidate_a' => 60,
                        'candidate_b' => 40,
                    ],
                ]
            )
            ->assertForbidden();

        $this->assertDatabaseMissing(
            'campaign_result_submissions',
            [
                'aspirant_contest_id' => $this->contestId,
                'electoral_polling_station_id' => $this->stationId,
            ]
        );
    }

    private function makeUser(string $suffix): User
    {
        $id = DB::table('users')->insertGetId([
            'name' => 'Campaign Agent Test ' . $suffix,
            'email' => 'campaign-agent-' . $suffix . '-'
                . bin2hex(random_bytes(5)) . '@example.test',
            'password' => bcrypt('test-password'),
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        return User::findOrFail($id);
    }
}
