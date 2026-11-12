<?php

namespace Tests\Feature;

use App\Services\MobilizerAccessService;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class MobilizerAccessServiceTest extends TestCase
{
    use DatabaseTransactions;

    private MobilizerAccessService $access;
    private int $ownerId;
    private int $mobilizerId;
    private int $contestId;
    private int $otherContestId;

    protected function setUp(): void
    {
        parent::setUp();

        $this->access = app(MobilizerAccessService::class);

        $this->ownerId = $this->makeUser('owner');
        $this->mobilizerId = $this->makeUser('mobilizer');
        $this->contestId = $this->makeContest($this->ownerId);
        $this->otherContestId = $this->makeContest(
            $this->makeUser('other-owner')
        );
    }

    public function test_active_mobilizer_can_access_assigned_campaign(): void
    {
        $this->makeAssignment('mobilizer', 'approved', true);

        $this->assertTrue(
            $this->access->canAccess(
                $this->mobilizerId,
                $this->contestId
            )
        );

        $this->assertCount(
            1,
            $this->access->activeAssignments($this->mobilizerId)
        );
    }

    public function test_cross_campaign_access_is_denied(): void
    {
        $this->makeAssignment('mobilizer', 'approved', true);

        $this->assertFalse(
            $this->access->canAccess(
                $this->mobilizerId,
                $this->otherContestId
            )
        );
    }

    public function test_ended_assignment_is_denied(): void
    {
        $this->makeAssignment(
            'mobilizer',
            'approved',
            true,
            'ended'
        );

        $this->assertFalse(
            $this->access->canAccess(
                $this->mobilizerId,
                $this->contestId
            )
        );
    }

    public function test_pending_application_is_denied(): void
    {
        $this->makeAssignment('mobilizer', 'pending', true);

        $this->assertFalse(
            $this->access->canAccess(
                $this->mobilizerId,
                $this->contestId
            )
        );
    }

    public function test_polling_station_agent_is_denied(): void
    {
        $this->makeAssignment(
            'polling_station_agent',
            'approved',
            true
        );

        $this->assertFalse(
            $this->access->canAccess(
                $this->mobilizerId,
                $this->contestId
            )
        );
    }

    public function test_unassigned_user_is_denied(): void
    {
        $this->assertFalse(
            $this->access->canAccess(
                $this->mobilizerId,
                $this->contestId
            )
        );
    }

    private function makeUser(string $suffix): int
    {
        return DB::table('users')->insertGetId([
            'name' => 'Mobilizer Access Test '.$suffix,
            'email' => 'mobilizer-access-'
                .bin2hex(random_bytes(8)).'@example.test',
            'password' => bcrypt('test-password'),
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    private function makeContest(int $ownerId): int
    {
        $aspirantId = DB::table('aspirants')->insertGetId([
            'user_id' => $ownerId,
            'display_name' => 'Mobilizer Access Test Campaign',
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

    private function makeAssignment(
        string $role,
        string $applicationStatus,
        bool $createSlot,
        string $assignmentStatus = 'assigned'
    ): void {
        $stationId = (int) DB::table('electoral_register_snapshots')
            ->where('dataset_id', 1)
            ->value('polling_station_id');

        if ($stationId < 1) {
            throw new \RuntimeException(
                'Isolated test database has no polling station fixture.'
            );
        }

        $applicationId = DB::table('role_applications')->insertGetId([
            'aspirant_contest_id' => $this->contestId,
            'applicant_user_id' => $this->mobilizerId,
            'electoral_dataset_id' => 1,
            'requested_role' => $role,
            'geographic_level' => 'polling_station',
            'polling_station_id' => $stationId,
            'status' => $applicationStatus,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $assignmentId = DB::table('field_assignments')->insertGetId([
            'role_application_id' => $applicationId,
            'assigned_by' => $this->ownerId,
            'status' => $assignmentStatus,
            'assigned_at' => now(),
            'ended_at' => $assignmentStatus === 'ended'
                ? now()
                : null,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        if ($createSlot) {
            DB::table('active_field_assignment_slots')->insert([
                'field_assignment_id' => $assignmentId,
                'aspirant_contest_id' => $this->contestId,
                'applicant_user_id' => $this->mobilizerId,
                'electoral_dataset_id' => 1,
                'requested_role' => $role,
                'geographic_level' => 'polling_station',
                'geographic_id' => $stationId,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }
    }
}
