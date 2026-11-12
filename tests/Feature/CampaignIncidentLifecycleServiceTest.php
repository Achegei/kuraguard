<?php

namespace Tests\Feature;

use App\Models\User;
use App\Services\CampaignIncidentLifecycleService;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use Symfony\Component\HttpKernel\Exception\HttpException;
use Tests\TestCase;

class CampaignIncidentLifecycleServiceTest extends TestCase
{
    use DatabaseTransactions;

    private User $owner;
    private User $admin;
    private User $reviewer;
    private User $outsider;
    private int $contestId;
    private int $otherContestId;
    private int $incidentId;
    private CampaignIncidentLifecycleService $lifecycle;

    protected function setUp(): void
    {
        parent::setUp();

        $this->lifecycle = app(CampaignIncidentLifecycleService::class);

        $this->owner = $this->makeUser('owner');
        $this->admin = $this->makeUser('admin');
        $this->reviewer = $this->makeUser('reviewer');
        $this->outsider = $this->makeUser('outsider');

        $aspirantId = $this->makeAspirant($this->owner->id);
        $this->contestId = $this->makeContest($aspirantId);

        $otherAspirantId = $this->makeAspirant($this->outsider->id);
        $this->otherContestId = $this->makeContest($otherAspirantId);

        $this->makeMembership($aspirantId, $this->admin->id, 'campaign_admin');
        $this->makeMembership($aspirantId, $this->reviewer->id, 'campaign_reviewer');

        $stationId = (int) DB::table('electoral_register_snapshots')
            ->where('dataset_id', 1)
            ->value('polling_station_id');

        if ($stationId < 1) {
            throw new \RuntimeException('Disposable dataset has no registered station.');
        }

        $this->incidentId = $this->makeIncident(
            $this->contestId,
            $stationId,
            $this->owner->id
        );
    }

    public function test_owner_can_acknowledge_incident_with_history_and_audit(): void
    {
        $this->lifecycle->transition(
            $this->owner,
            $this->contestId,
            $this->incidentId,
            'acknowledged',
            'Coordinator has received the report.'
        );

        $this->assertDatabaseHas('campaign_incidents', [
            'id' => $this->incidentId,
            'status' => 'acknowledged',
            'resolved_at' => null,
        ]);

        $this->assertDatabaseHas('campaign_incident_status_history', [
            'campaign_incident_id' => $this->incidentId,
            'aspirant_contest_id' => $this->contestId,
            'changed_by' => $this->owner->id,
            'from_status' => 'open',
            'to_status' => 'acknowledged',
            'notes' => 'Coordinator has received the report.',
        ]);

        $this->assertDatabaseHas('audit_logs', [
            'user_id' => $this->owner->id,
            'action' => 'campaign.incident.status_changed',
            'entity_type' => 'campaign_incident',
            'entity_id' => $this->incidentId,
        ]);

        $this->assertSame(1, $this->historyCount());
    }

    public function test_admin_can_escalate_incident(): void
    {
        $this->lifecycle->transition(
            $this->admin,
            $this->contestId,
            $this->incidentId,
            'escalated',
            'Escalated to campaign leadership.'
        );

        $this->assertIncidentStatus('escalated');
        $this->assertSame(1, $this->historyCount());
    }

    public function test_valid_sequence_can_resolve_incident(): void
    {
        foreach ([
            ['acknowledged', 'Report acknowledged.'],
            ['investigating', 'Investigation started.'],
            ['resolved', 'Incident verified and resolved.'],
        ] as [$status, $notes]) {
            $this->lifecycle->transition(
                $this->owner,
                $this->contestId,
                $this->incidentId,
                $status,
                $notes
            );
        }

        $incident = DB::table('campaign_incidents')
            ->where('id', $this->incidentId)
            ->first();

        $this->assertSame('resolved', $incident->status);
        $this->assertNotNull($incident->resolved_at);
        $this->assertSame(3, $this->historyCount());
    }

    public function test_invalid_transition_is_rejected_without_writes(): void
    {
        try {
            $this->lifecycle->transition(
                $this->owner,
                $this->contestId,
                $this->incidentId,
                'resolved',
                'Attempted to skip investigation.'
            );

            $this->fail('Expected transition validation failure.');
        } catch (ValidationException $exception) {
            $this->assertArrayHasKey('status', $exception->errors());
        }

        $this->assertIncidentStatus('open');
        $this->assertSame(0, $this->historyCount());
        $this->assertSame(0, $this->statusChangeAuditCount());
    }

    public function test_resolution_requires_notes(): void
    {
        $this->lifecycle->transition(
            $this->owner,
            $this->contestId,
            $this->incidentId,
            'escalated',
            'Escalated for urgent review.'
        );

        try {
            $this->lifecycle->transition(
                $this->owner,
                $this->contestId,
                $this->incidentId,
                'resolved',
                '   '
            );

            $this->fail('Expected resolution notes validation failure.');
        } catch (ValidationException $exception) {
            $this->assertArrayHasKey('notes', $exception->errors());
        }

        $this->assertIncidentStatus('escalated');
        $this->assertSame(1, $this->historyCount());
    }

    public function test_reviewer_cannot_change_incident_status(): void
    {
        $this->expectException(AuthorizationException::class);

        try {
            $this->lifecycle->transition(
                $this->reviewer,
                $this->contestId,
                $this->incidentId,
                'acknowledged'
            );
        } finally {
            $this->assertIncidentStatus('open');
            $this->assertSame(0, $this->historyCount());
        }
    }

    public function test_outsider_cannot_change_incident_status(): void
    {
        $this->expectException(AuthorizationException::class);

        try {
            $this->lifecycle->transition(
                $this->outsider,
                $this->contestId,
                $this->incidentId,
                'acknowledged'
            );
        } finally {
            $this->assertIncidentStatus('open');
            $this->assertSame(0, $this->historyCount());
        }
    }

    public function test_incident_cannot_be_updated_through_another_campaign(): void
    {
        $this->expectException(HttpException::class);

        try {
            $this->lifecycle->transition(
                $this->outsider,
                $this->otherContestId,
                $this->incidentId,
                'acknowledged'
            );
        } finally {
            $this->assertIncidentStatus('open');
            $this->assertSame(0, $this->historyCount());
        }
    }

    public function test_repeated_transition_is_rejected(): void
    {
        $this->lifecycle->transition(
            $this->owner,
            $this->contestId,
            $this->incidentId,
            'acknowledged'
        );

        try {
            $this->lifecycle->transition(
                $this->owner,
                $this->contestId,
                $this->incidentId,
                'acknowledged'
            );

            $this->fail('Expected repeated transition to fail.');
        } catch (ValidationException $exception) {
            $this->assertArrayHasKey('status', $exception->errors());
        }

        $this->assertSame(1, $this->historyCount());
        $this->assertSame(1, $this->statusChangeAuditCount());
    }

    private function assertIncidentStatus(string $status): void
    {
        $this->assertSame(
            $status,
            DB::table('campaign_incidents')
                ->where('id', $this->incidentId)
                ->value('status')
        );
    }

    private function historyCount(): int
    {
        return DB::table('campaign_incident_status_history')
            ->where('campaign_incident_id', $this->incidentId)
            ->count();
    }

    private function statusChangeAuditCount(): int
    {
        return DB::table('audit_logs')
            ->where('action', 'campaign.incident.status_changed')
            ->where('entity_type', 'campaign_incident')
            ->where('entity_id', $this->incidentId)
            ->count();
    }

    private function makeUser(string $suffix): User
    {
        $userId = DB::table('users')->insertGetId([
            'name' => 'Incident Lifecycle '.$suffix,
            'email' => 'incident-'.bin2hex(random_bytes(8)).'@example.test',
            'password' => bcrypt('test-password'),
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        return User::findOrFail($userId);
    }

    private function makeAspirant(int $userId): int
    {
        return DB::table('aspirants')->insertGetId([
            'user_id' => $userId,
            'display_name' => 'Incident Lifecycle Test Aspirant',
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    private function makeContest(int $aspirantId): int
    {
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

    private function makeMembership(
        int $aspirantId,
        int $userId,
        string $role
    ): void {
        DB::table('aspirant_memberships')->insert([
            'aspirant_id' => $aspirantId,
            'user_id' => $userId,
            'role' => $role,
            'status' => 'active',
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    private function makeIncident(
        int $contestId,
        int $stationId,
        int $reporterId
    ): int {
        return DB::table('campaign_incidents')->insertGetId([
            'aspirant_contest_id' => $contestId,
            'electoral_polling_station_id' => $stationId,
            'reported_by' => $reporterId,
            'category' => 'lifecycle_test',
            'severity' => 'high',
            'description' => 'Incident lifecycle regression test.',
            'status' => 'open',
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }
}
