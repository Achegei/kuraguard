<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class CampaignIncidentTransitionHttpTest extends TestCase
{
    use DatabaseTransactions;

    private User $owner;
    private User $admin;
    private User $reviewer;
    private User $outsider;

    private int $contestId;
    private int $otherContestId;
    private int $incidentId;

    protected function setUp(): void
    {
        parent::setUp();

        $this->owner = $this->makeUser('owner');
        $this->admin = $this->makeUser('admin');
        $this->reviewer = $this->makeUser('reviewer');
        $this->outsider = $this->makeUser('outsider');

        $aspirantId = $this->makeAspirant(
            $this->owner->id
        );

        $this->contestId = $this->makeContest(
            $aspirantId
        );

        $otherAspirantId = $this->makeAspirant(
            $this->outsider->id
        );

        $this->otherContestId = $this->makeContest(
            $otherAspirantId
        );

        $this->makeMembership(
            $aspirantId,
            $this->admin->id,
            'campaign_admin'
        );

        $this->makeMembership(
            $aspirantId,
            $this->reviewer->id,
            'campaign_reviewer'
        );

        $stationId = (int) DB::table(
            'electoral_register_snapshots'
        )
            ->where('dataset_id', 1)
            ->value('polling_station_id');

        if ($stationId < 1) {
            throw new \RuntimeException(
                'Disposable dataset has no registered station.'
            );
        }

        $this->incidentId = DB::table(
            'campaign_incidents'
        )->insertGetId([
            'aspirant_contest_id' => $this->contestId,
            'electoral_polling_station_id' => $stationId,
            'reported_by' => $this->owner->id,
            'category' => 'http_lifecycle_test',
            'severity' => 'high',
            'description' => 'HTTP lifecycle regression incident.',
            'status' => 'open',
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    public function test_owner_can_acknowledge_incident(): void
    {
        $this->actingAs($this->owner)
            ->postJson($this->url(), [
                'status' => 'acknowledged',
                'notes' => 'Incident received.',
            ])
            ->assertOk()
            ->assertJsonPath('status', 'success')
            ->assertJsonPath(
                'incident_status',
                'acknowledged'
            );

        $this->assertIncidentStatus('acknowledged');
        $this->assertSame(1, $this->historyCount());
        $this->assertSame(1, $this->auditCount());
    }

    public function test_admin_can_escalate_incident(): void
    {
        $this->actingAs($this->admin)
            ->postJson($this->url(), [
                'status' => 'escalated',
                'notes' => 'Escalated to leadership.',
            ])
            ->assertOk()
            ->assertJsonPath(
                'incident_status',
                'escalated'
            );

        $this->assertIncidentStatus('escalated');
        $this->assertSame(1, $this->historyCount());
    }

    public function test_reviewer_cannot_transition_incident(): void
    {
        $this->actingAs($this->reviewer)
            ->postJson($this->url(), [
                'status' => 'acknowledged',
            ])
            ->assertForbidden();

        $this->assertNoChanges();
    }

    public function test_outsider_cannot_transition_incident(): void
    {
        $this->actingAs($this->outsider)
            ->postJson($this->url(), [
                'status' => 'acknowledged',
            ])
            ->assertForbidden();

        $this->assertNoChanges();
    }

    public function test_unauthenticated_request_is_rejected(): void
    {
        $this->postJson($this->url(), [
            'status' => 'acknowledged',
        ])->assertUnauthorized();

        $this->assertNoChanges();
    }

    public function test_invalid_status_is_rejected(): void
    {
        $this->actingAs($this->owner)
            ->postJson($this->url(), [
                'status' => 'deleted',
            ])
            ->assertUnprocessable()
            ->assertJsonValidationErrors('status');

        $this->assertNoChanges();
    }

    public function test_invalid_transition_is_rejected(): void
    {
        $this->actingAs($this->owner)
            ->postJson($this->url(), [
                'status' => 'resolved',
                'notes' => 'Attempted direct resolution.',
            ])
            ->assertUnprocessable()
            ->assertJsonValidationErrors('status');

        $this->assertNoChanges();
    }

    public function test_resolution_requires_notes(): void
    {
        $this->actingAs($this->owner)
            ->postJson($this->url(), [
                'status' => 'escalated',
                'notes' => 'Escalated for investigation.',
            ])
            ->assertOk();

        $this->actingAs($this->owner)
            ->postJson($this->url(), [
                'status' => 'resolved',
            ])
            ->assertUnprocessable()
            ->assertJsonValidationErrors('notes');

        $this->assertIncidentStatus('escalated');
        $this->assertSame(1, $this->historyCount());
        $this->assertSame(1, $this->auditCount());
    }

    public function test_other_campaign_incident_returns_not_found(): void
    {
        $this->actingAs($this->outsider)
            ->postJson(
                $this->url(
                    $this->otherContestId
                ),
                [
                    'status' => 'acknowledged',
                ]
            )
            ->assertNotFound();

        $this->assertNoChanges();
    }

    public function test_missing_incident_returns_not_found(): void
    {
        $this->actingAs($this->owner)
            ->postJson(
                $this->url(
                    $this->contestId,
                    999999999
                ),
                [
                    'status' => 'acknowledged',
                ]
            )
            ->assertNotFound();

        $this->assertNoChanges();
    }

    public function test_repeated_transition_does_not_duplicate_history(): void
    {
        $this->actingAs($this->owner)
            ->postJson($this->url(), [
                'status' => 'acknowledged',
            ])
            ->assertOk();

        $this->actingAs($this->owner)
            ->postJson($this->url(), [
                'status' => 'acknowledged',
            ])
            ->assertUnprocessable()
            ->assertJsonValidationErrors('status');

        $this->assertIncidentStatus('acknowledged');
        $this->assertSame(1, $this->historyCount());
        $this->assertSame(1, $this->auditCount());
    }


    public function test_browser_transition_redirects_to_monitor(): void
    {
        $this->actingAs($this->owner)
            ->post($this->url(), [
                'status' => 'acknowledged',
                'notes' => 'Received by campaign manager.',
            ])
            ->assertRedirect(
                route('campaign.operations.monitor', [
                    'contestId' => $this->contestId,
                ])
            )
            ->assertSessionHas('status');

        $this->assertIncidentStatus('acknowledged');
        $this->assertSame(1, $this->historyCount());
        $this->assertSame(1, $this->auditCount());
    }

    public function test_browser_invalid_transition_redirects_with_errors(): void
    {
        $this->actingAs($this->owner)
            ->post($this->url(), [
                'status' => 'resolved',
                'notes' => 'Invalid direct resolution.',
            ])
            ->assertSessionHasErrors('status');

        $this->assertNoChanges();
    }

    public function test_owner_sees_incident_management_controls(): void
    {
        $this->actingAs($this->owner)
            ->get($this->monitorUrl())
            ->assertOk()
            ->assertSee('Management Actions')
            ->assertSee('Update Incident')
            ->assertSee('Lifecycle History')
            ->assertSee('incident-status-'.$this->incidentId);
    }

    public function test_reviewer_cannot_see_incident_management_controls(): void
    {
        $this->actingAs($this->reviewer)
            ->get($this->monitorUrl())
            ->assertOk()
            ->assertSee('Incident Monitoring')
            ->assertDontSee('Management Actions')
            ->assertDontSee('Update Incident')
            ->assertDontSee('incident-status-'.$this->incidentId);
    }

    public function test_incident_history_is_scoped_to_campaign(): void
    {
        $this->actingAs($this->owner)
            ->postJson($this->url(), [
                'status' => 'acknowledged',
                'notes' => 'Unique campaign history marker.',
            ])
            ->assertOk();

        $this->actingAs($this->owner)
            ->get($this->monitorUrl())
            ->assertOk()
            ->assertSee('Unique campaign history marker.');

        $this->actingAs($this->outsider)
            ->get($this->monitorUrl($this->otherContestId))
            ->assertOk()
            ->assertDontSee('Unique campaign history marker.');
    }

    private function monitorUrl(?int $contestId = null): string
    {
        return route(
            'campaign.operations.monitor',
            [
                'contestId' => $contestId ?? $this->contestId,
            ],
            false
        );
    }

    private function url(
        ?int $contestId = null,
        ?int $incidentId = null
    ): string {
        return route(
            'campaign.operations.incidents.transition',
            [
                'contestId' => $contestId ?? $this->contestId,
                'incidentId' => $incidentId ?? $this->incidentId,
            ],
            false
        );
    }

    private function assertNoChanges(): void
    {
        $this->assertIncidentStatus('open');
        $this->assertSame(0, $this->historyCount());
        $this->assertSame(0, $this->auditCount());
    }

    private function assertIncidentStatus(
        string $expected
    ): void {
        $this->assertSame(
            $expected,
            DB::table('campaign_incidents')
                ->where('id', $this->incidentId)
                ->value('status')
        );
    }

    private function historyCount(): int
    {
        return DB::table(
            'campaign_incident_status_history'
        )
            ->where(
                'campaign_incident_id',
                $this->incidentId
            )
            ->count();
    }

    private function auditCount(): int
    {
        return DB::table('audit_logs')
            ->where(
                'action',
                'campaign.incident.status_changed'
            )
            ->where(
                'entity_type',
                'campaign_incident'
            )
            ->where(
                'entity_id',
                $this->incidentId
            )
            ->count();
    }

    private function makeUser(
        string $suffix
    ): User {
        $id = DB::table('users')->insertGetId([
            'name' => 'Incident HTTP '.$suffix,
            'email' => 'incident-http-'
                .bin2hex(random_bytes(8))
                .'@example.test',
            'password' => bcrypt('test-password'),
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        return User::findOrFail($id);
    }

    private function makeAspirant(
        int $userId
    ): int {
        return DB::table('aspirants')->insertGetId([
            'user_id' => $userId,
            'display_name' => 'Incident HTTP Test Aspirant',
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    private function makeContest(
        int $aspirantId
    ): int {
        return DB::table(
            'aspirant_contests'
        )->insertGetId([
            'contest_identity_key' => 'test-'
                .bin2hex(random_bytes(16)),
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
}
