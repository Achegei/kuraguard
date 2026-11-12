<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class CampaignScenarioGeographyHttpTest extends TestCase
{
    use RefreshDatabase;

    private function fixture(): array
    {
        $now = now();
        $dataset = DB::table('electoral_datasets')->insertGetId([
            'name' => 'B1 Primary', 'version' => 'b1-'.bin2hex(random_bytes(8)),
            'source_organization' => 'Test', 'reference_year' => 2022,
            'status' => 'historical', 'created_at' => $now, 'updated_at' => $now,
        ]);
        $alternate = DB::table('electoral_datasets')->insertGetId([
            'name' => 'B1 Alternate', 'version' => 'b1-'.bin2hex(random_bytes(8)),
            'source_organization' => 'Test', 'reference_year' => 2022,
            'status' => 'historical', 'created_at' => $now, 'updated_at' => $now,
        ]);
        $county = DB::table('electoral_counties')->insertGetId([
            'code' => 'B1-C-'.bin2hex(random_bytes(4)), 'name' => 'B1 County',
            'created_at' => $now, 'updated_at' => $now,
        ]);
        $otherCounty = DB::table('electoral_counties')->insertGetId([
            'code' => 'B1-C-'.bin2hex(random_bytes(4)), 'name' => 'Other County',
            'created_at' => $now, 'updated_at' => $now,
        ]);
        $constituency = DB::table('electoral_constituencies')->insertGetId([
            'county_id' => $county, 'code' => 'B1-CO-'.bin2hex(random_bytes(4)),
            'name' => 'B1 Constituency', 'created_at' => $now, 'updated_at' => $now,
        ]);
        $otherConstituency = DB::table('electoral_constituencies')->insertGetId([
            'county_id' => $otherCounty, 'code' => 'B1-CO-'.bin2hex(random_bytes(4)),
            'name' => 'Other Constituency', 'created_at' => $now, 'updated_at' => $now,
        ]);
        $ward = DB::table('electoral_wards')->insertGetId([
            'constituency_id' => $constituency, 'code' => 'B1-W-'.bin2hex(random_bytes(4)),
            'name' => 'B1 Ward', 'created_at' => $now, 'updated_at' => $now,
        ]);
        $otherWard = DB::table('electoral_wards')->insertGetId([
            'constituency_id' => $otherConstituency, 'code' => 'B1-W-'.bin2hex(random_bytes(4)),
            'name' => 'Other Ward', 'created_at' => $now, 'updated_at' => $now,
        ]);
        $centre = DB::table('electoral_polling_centres')->insertGetId([
            'ward_id' => $ward, 'code' => 'B1-PC-'.bin2hex(random_bytes(4)),
            'name' => 'B1 Centre', 'created_at' => $now, 'updated_at' => $now,
        ]);
        $station = DB::table('electoral_polling_stations')->insertGetId([
            'polling_centre_id' => $centre, 'code' => 'B1-PS-'.bin2hex(random_bytes(4)),
            'name' => 'B1 Station', 'created_at' => $now, 'updated_at' => $now,
        ]);
        DB::table('electoral_register_snapshots')->insert([
            ['dataset_id' => $dataset, 'polling_station_id' => $station,
                'registered_voters' => 321, 'created_at' => $now, 'updated_at' => $now],
            ['dataset_id' => $alternate, 'polling_station_id' => $station,
                'registered_voters' => 999, 'created_at' => $now, 'updated_at' => $now],
        ]);
        DB::table('electoral_polling_stations')->insert([
            'polling_centre_id' => $centre, 'code' => 'B1-PS-'.bin2hex(random_bytes(4)),
            'name' => 'Missing Snapshot', 'created_at' => $now, 'updated_at' => $now,
        ]);
        $owner = User::factory()->create();
        $outsider = User::factory()->create();
        $aspirant = DB::table('aspirants')->insertGetId([
            'user_id' => $owner->id, 'display_name' => 'B1 Aspirant',
            'created_at' => $now, 'updated_at' => $now,
        ]);
        $contest = DB::table('aspirant_contests')->insertGetId([
            'aspirant_id' => $aspirant, 'electoral_dataset_id' => $dataset,
            'contest_identity_key' => 'b1-'.bin2hex(random_bytes(16)),
            'election_year' => 2027, 'office' => 'mca', 'ward_id' => $ward,
            'created_at' => $now, 'updated_at' => $now,
        ]);
        return compact('dataset', 'county', 'constituency', 'ward', 'otherWard',
            'centre', 'contest', 'owner', 'outsider');
    }

    public function test_guest_is_denied(): void
    {
        $f = $this->fixture();
        $this->get(route('campaign.scenarios.geography', $f['contest']).'?level=polling_centre')
            ->assertRedirect();
    }

    public function test_outsider_is_forbidden(): void
    {
        $f = $this->fixture();
        $this->actingAs($f['outsider'])
            ->getJson(route('campaign.scenarios.geography', $f['contest']).'?level=polling_centre')
            ->assertForbidden();
    }

    public function test_mca_is_scoped_and_uses_campaign_dataset_with_missing_snapshot_metadata(): void
    {
        $f = $this->fixture();
        $url = route('campaign.scenarios.geography', $f['contest']);
        $this->actingAs($f['owner'])->getJson($url.'?level=polling_centre')
            ->assertOk()
            ->assertJsonPath('meta.dataset_id', $f['dataset'])
            ->assertJsonPath('meta.total', 1)
            ->assertJsonPath('data.0.id', $f['centre'])
            ->assertJsonPath('data.0.registered_voters', 321)
            ->assertJsonPath('data.0.polling_stations', 2)
            ->assertJsonPath('data.0.stations_missing_snapshot', 1)
            ->assertJsonPath('data.0.register_complete', false);
        $this->actingAs($f['owner'])->getJson($url.'?level=polling_centre&parent_id='.$f['otherWard'])
            ->assertUnprocessable();
        $this->actingAs($f['owner'])->getJson($url.'?level=county')
            ->assertUnprocessable();
    }

    public function test_county_and_national_navigation_and_cross_county_denial(): void
    {
        $f = $this->fixture();
        $url = route('campaign.scenarios.geography', $f['contest']);
        DB::table('aspirant_contests')->where('id', $f['contest'])->update([
            'office' => 'governor', 'ward_id' => null, 'county_id' => $f['county'],
        ]);
        $this->actingAs($f['owner'])->getJson($url.'?level=constituency')
            ->assertOk()->assertJsonPath('data.0.id', $f['constituency']);
        $this->actingAs($f['owner'])->getJson($url.'?level=ward&parent_id='.$f['constituency'])
            ->assertOk()->assertJsonPath('data.0.id', $f['ward']);
        $this->actingAs($f['owner'])->getJson($url.'?level=polling_centre&parent_id='.$f['otherWard'])
            ->assertUnprocessable();
        DB::table('aspirant_contests')->where('id', $f['contest'])->update([
            'office' => 'president', 'county_id' => null,
        ]);
        $this->actingAs($f['owner'])->getJson($url.'?level=county')
            ->assertOk()->assertJsonPath('meta.level', 'county');
    }
}
