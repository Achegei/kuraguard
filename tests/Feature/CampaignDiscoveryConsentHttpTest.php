<?php

namespace Tests\Feature;

use App\Models\User;
use App\Services\ElectoralScopeResolver;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Mockery;
use Tests\TestCase;

class CampaignDiscoveryConsentHttpTest extends TestCase
{
    use RefreshDatabase;

    private int $datasetId;
    private int $wardId;
    private int $otherWardId;
    private int $otherDatasetId;

    protected function setUp(): void
    {
        parent::setUp();
        $this->datasetId = $this->makeDataset('primary');
        $this->otherDatasetId = $this->makeDataset('alternate');
        $countyId = DB::table('electoral_counties')->insertGetId([
            'code' => 'CONSENT-COUNTY-'.bin2hex(random_bytes(4)),
            'name' => 'Consent Test County', 'created_at' => now(), 'updated_at' => now(),
        ]);
        $constituencyId = DB::table('electoral_constituencies')->insertGetId([
            'county_id' => $countyId,
            'code' => 'CONSENT-CONST-'.bin2hex(random_bytes(4)),
            'name' => 'Consent Test Constituency', 'created_at' => now(), 'updated_at' => now(),
        ]);
        $this->wardId = $this->makeWard($constituencyId, 'Primary Ward');
        $this->otherWardId = $this->makeWard($constituencyId, 'Other Ward');
    }

    private function makeDataset(string $suffix): int
    {
        return DB::table('electoral_datasets')->insertGetId([
            'name' => 'Consent Test Dataset '.$suffix,
            'version' => 'consent-test-'.bin2hex(random_bytes(8)),
            'source_organization' => 'Test Fixture',
            'reference_year' => 2022, 'status' => 'historical',
            'created_at' => now(), 'updated_at' => now(),
        ]);
    }

    private function makeWard(int $constituencyId, string $name): int
    {
        return DB::table('electoral_wards')->insertGetId([
            'constituency_id' => $constituencyId,
            'code' => 'CONSENT-WARD-'.bin2hex(random_bytes(4)),
            'name' => $name, 'created_at' => now(), 'updated_at' => now(),
        ]);
    }

    private function user(string $suffix): User
    {
        return User::findOrFail(DB::table('users')->insertGetId([
            'name' => 'Consent Test '.$suffix,
            'email' => 'consent-'.bin2hex(random_bytes(5)).'@example.test',
            'password' => bcrypt('test-password'),
            'created_at' => now(), 'updated_at' => now(),
        ]));
    }

    private function contest(User $owner, string $name, array $changes = []): int
    {
        $aspirantId = DB::table('aspirants')->insertGetId([
            'user_id' => $owner->id, 'display_name' => $name,
            'created_at' => now(), 'updated_at' => now(),
        ]);
        return DB::table('aspirant_contests')->insertGetId(array_merge([
            'contest_identity_key' => 'consent-test-'.bin2hex(random_bytes(16)),
            'aspirant_id' => $aspirantId, 'electoral_dataset_id' => $this->datasetId,
            'election_year' => 2027, 'office' => 'mca', 'ward_id' => $this->wardId,
            'created_at' => now(), 'updated_at' => now(),
        ], $changes));
    }

    private function consent(int $contestId): bool
    {
        return (bool) DB::table('aspirant_contests')->where('id', $contestId)
            ->value('scenario_discovery_consent');
    }

    private function scenario(User $viewer, int $contestId): \Illuminate\Testing\TestResponse
    {
        // Mock only geographic statistics; exercise the real HTTP authorization and discovery query.
        $resolver = Mockery::mock(ElectoralScopeResolver::class);
        $resolver->shouldReceive('resolve')->once()->andReturn([
            'office' => 'mca', 'office_label' => 'Member of County Assembly',
            'scope_level' => 'ward', 'geographic_id' => $this->wardId,
            'geographic_name' => 'Test Ward', 'navigation_levels' => [],
            'dataset' => ['id' => $this->datasetId, 'name' => 'Test', 'reference_year' => 2022, 'status' => 'historical'],
            'statistics' => ['polling_centres' => 0, 'polling_stations' => 0, 'registered_voters' => 0],
        ]);
        $this->app->instance(ElectoralScopeResolver::class, $resolver);
        return $this->actingAs($viewer)->get(route('campaign.scenarios.index', ['contestId' => $contestId]));
    }

    private function competitorNames(\Illuminate\Testing\TestResponse $response): array
    {
        $response->assertOk();
        $names = $response->viewData('registeredCompetitors');
        $this->assertNotNull($names);
        return collect($names)->pluck('name')->all();
    }

    public function test_guest_cannot_open_discovery_settings(): void
    {
        $this->assertContains($this->get(route('campaign.discovery-settings.show', ['contestId' => 999]))->status(), [302, 401, 403]);
    }

    public function test_discovery_consent_defaults_to_private_in_database_schema(): void
    {
        $this->assertTrue(Schema::hasColumn('aspirant_contests', 'scenario_discovery_consent'));
        $id = $this->contest($this->user('default'), 'Default Private');
        $this->assertFalse($this->consent($id));
    }

    public function test_owner_can_enable_and_withdraw_consent(): void
    {
        $owner = $this->user('owner');
        $id = $this->contest($owner, 'Owner Aspirant');
        $url = route('campaign.discovery-settings.update', ['contestId' => $id]);
        $this->actingAs($owner)->get(route('campaign.discovery-settings.show', ['contestId' => $id]))->assertOk();
        $this->actingAs($owner)->post($url, ['scenario_discovery_consent' => 1])->assertRedirect();
        $this->assertTrue($this->consent($id));
        $this->actingAs($owner)->get(route('campaign.discovery-settings.show', ['contestId' => $id]))
            ->assertOk()->assertSee('Discoverable');
        $this->actingAs($owner)->post($url, ['scenario_discovery_consent' => 0])->assertRedirect();
        $this->assertFalse($this->consent($id));
    }

    public function test_non_owner_cannot_read_or_change_consent(): void
    {
        $owner = $this->user('owner');
        $outsider = $this->user('outsider');
        $id = $this->contest($owner, 'Private Owner');
        $url = route('campaign.discovery-settings.update', ['contestId' => $id]);
        $this->actingAs($outsider)->get(route('campaign.discovery-settings.show', ['contestId' => $id]))->assertForbidden();
        $this->actingAs($outsider)->post($url, ['scenario_discovery_consent' => 1])->assertForbidden();
        $this->assertFalse($this->consent($id));
    }

    public function test_invalid_consent_payload_does_not_change_database(): void
    {
        $owner = $this->user('invalid');
        $id = $this->contest($owner, 'Invalid Payload');
        $this->actingAs($owner)->post(route('campaign.discovery-settings.update', ['contestId' => $id]), [
            'scenario_discovery_consent' => 'unexpected',
        ])->assertSessionHasErrors('scenario_discovery_consent');
        $this->assertFalse($this->consent($id));
    }

    public function test_only_opted_in_same_seat_aspirants_are_discovered(): void
    {
        $viewer = $this->user('viewer');
        $ownId = $this->contest($viewer, 'Viewer Campaign');
        $visibleId = $this->contest($this->user('visible'), 'Visible Same Seat', ['scenario_discovery_consent' => true]);
        $this->contest($this->user('private'), 'Private Same Seat');
        $this->contest($this->user('ward'), 'Different Ward', ['scenario_discovery_consent' => true, 'ward_id' => $this->otherWardId]);
        $this->contest($this->user('year'), 'Different Year', ['scenario_discovery_consent' => true, 'election_year' => 2028]);
        $this->contest($this->user('office'), 'Different Office', ['scenario_discovery_consent' => true, 'office' => 'president', 'ward_id' => null]);
        $this->contest($this->user('dataset'), 'Different Dataset', ['scenario_discovery_consent' => true, 'electoral_dataset_id' => $this->otherDatasetId]);
        config()->set('kuraguard.scenario_public_aspirant_discovery', true);
        $names = $this->competitorNames($this->scenario($viewer, $ownId));
        $this->assertSame(['Visible Same Seat'], $names);
        $this->assertNotContains('Viewer Campaign', $names);
        $this->assertGreaterThan(0, $visibleId);
    }

    public function test_withdrawal_removes_aspirant_from_newly_loaded_scenarios(): void
    {
        $viewer = $this->user('viewer');
        $ownId = $this->contest($viewer, 'Viewer Campaign');
        $owner = $this->user('competitor');
        $competitorId = $this->contest($owner, 'Withdrawn Competitor', ['scenario_discovery_consent' => true]);
        config()->set('kuraguard.scenario_public_aspirant_discovery', true);
        $this->assertContains('Withdrawn Competitor', $this->competitorNames($this->scenario($viewer, $ownId)));
        $this->actingAs($owner)->post(route('campaign.discovery-settings.update', ['contestId' => $competitorId]), [
            'scenario_discovery_consent' => 0,
        ])->assertRedirect();
        $this->assertNotContains('Withdrawn Competitor', $this->competitorNames($this->scenario($viewer, $ownId)));
    }

    public function test_discovery_feature_flag_blocks_all_competitors(): void
    {
        $viewer = $this->user('viewer');
        $ownId = $this->contest($viewer, 'Viewer Campaign');
        $this->contest($this->user('competitor'), 'Opted In But Disabled', ['scenario_discovery_consent' => true]);
        config()->set('kuraguard.scenario_public_aspirant_discovery', false);
        $this->assertSame([], $this->competitorNames($this->scenario($viewer, $ownId)));
    }
}
