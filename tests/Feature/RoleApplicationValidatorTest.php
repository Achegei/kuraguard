<?php

namespace Tests\Feature;

use App\Enums\ElectoralFieldRole;
use App\Services\RoleApplicationValidator;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use Tests\TestCase;

class RoleApplicationValidatorTest extends TestCase
{
    use DatabaseTransactions;

    private RoleApplicationValidator $validator;
    private int $contestId;
    private object $geography;

    protected function setUp(): void
    {
        parent::setUp();

        $this->validator = app(RoleApplicationValidator::class);

        $this->geography = DB::table('electoral_polling_stations as ps')
            ->join('electoral_polling_centres as pc', 'pc.id', '=', 'ps.polling_centre_id')
            ->join('electoral_wards as w', 'w.id', '=', 'pc.ward_id')
            ->join('electoral_constituencies as co', 'co.id', '=', 'w.constituency_id')
            ->join('electoral_counties as c', 'c.id', '=', 'co.county_id')
            ->select(
                'c.id as county_id',
                'co.id as constituency_id',
                'w.id as ward_id',
                'pc.id as polling_centre_id',
                'ps.id as polling_station_id'
            )
            ->first();

        $this->assertNotNull($this->geography);

        $aspirantId = DB::table('aspirants')->insertGetId([
            'user_id' => DB::table('users')->value('id'),
            'display_name' => 'Test Aspirant',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $this->contestId = DB::table('aspirant_contests')->insertGetId([
            'contest_identity_key' => 'test-' . bin2hex(random_bytes(16)),
            'aspirant_id' => $aspirantId,
            'electoral_dataset_id' => 1,
            'election_year' => 2027,
            'office' => 'mca',
            'county_id' => $this->geography->county_id,
            'constituency_id' => $this->geography->constituency_id,
            'ward_id' => $this->geography->ward_id,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    public function test_valid_polling_station_agent_application(): void
    {
        $result = $this->validator->validate(
            $this->contestId,
            ElectoralFieldRole::POLLING_STATION_AGENT,
            'polling_station',
            $this->geography->polling_station_id,
            1
        );

        $this->assertSame(
            'polling_station_agent',
            $result['requested_role']
        );

        $this->assertSame(
            (int) $this->geography->ward_id,
            $result['ward_id']
        );
    }


    public function test_polling_station_has_matching_dataset_snapshot(): void
    {
        $exists = DB::table('electoral_register_snapshots')
            ->where('dataset_id', 1)
            ->where(
                'polling_station_id',
                $this->geography->polling_station_id
            )
            ->exists();

        $this->assertTrue($exists);

        $result = $this->validator->validate(
            $this->contestId,
            ElectoralFieldRole::POLLING_STATION_AGENT,
            'polling_station',
            $this->geography->polling_station_id,
            1
        );

        $this->assertSame(
            'polling_station_agent',
            $result['requested_role']
        );
    }

    public function test_rejects_station_without_dataset_snapshot(): void
    {
        $deleted = DB::table('electoral_register_snapshots')
            ->where('dataset_id', 1)
            ->where(
                'polling_station_id',
                $this->geography->polling_station_id
            )
            ->delete();

        $this->assertSame(1, $deleted);

        $this->expectException(ValidationException::class);

        $this->validator->validate(
            $this->contestId,
            ElectoralFieldRole::POLLING_STATION_AGENT,
            'polling_station',
            $this->geography->polling_station_id,
            1
        );
    }

    public function test_valid_ward_coordinator_application(): void
    {
        $result = $this->validator->validate(
            $this->contestId,
            ElectoralFieldRole::WARD_COORDINATOR,
            'ward',
            $this->geography->ward_id,
            1
        );

        $this->assertSame('ward', $result['geographic_level']);
    }

    public function test_rejects_role_level_mismatch(): void
    {
        $this->expectException(ValidationException::class);

        $this->validator->validate(
            $this->contestId,
            ElectoralFieldRole::POLLING_STATION_AGENT,
            'county',
            $this->geography->county_id,
            1
        );
    }

    public function test_rejects_missing_contest(): void
    {
        $this->expectException(ValidationException::class);

        $this->validator->validate(
            999999999,
            ElectoralFieldRole::WARD_COORDINATOR,
            'ward',
            $this->geography->ward_id,
            1
        );
    }

    public function test_rejects_dataset_mismatch(): void
    {
        $this->expectException(ValidationException::class);

        $this->validator->validate(
            $this->contestId,
            ElectoralFieldRole::WARD_COORDINATOR,
            'ward',
            $this->geography->ward_id,
            999999999
        );
    }

    public function test_rejects_location_outside_contest(): void
    {
        $otherWard = DB::table('electoral_wards')
            ->where('id', '!=', $this->geography->ward_id)
            ->value('id');

        $this->assertNotNull($otherWard);

        $this->expectException(ValidationException::class);

        $this->validator->validate(
            $this->contestId,
            ElectoralFieldRole::WARD_COORDINATOR,
            'ward',
            (int) $otherWard,
            1
        );
    }

    public function test_valid_mobilizer_ward_application(): void
    {
        $result = $this->validator->validate(
            $this->contestId,
            ElectoralFieldRole::MOBILIZER,
            'ward',
            $this->geography->ward_id,
            1
        );

        $this->assertSame('mobilizer', $result['requested_role']);
        $this->assertSame('ward', $result['geographic_level']);
    }

    public function test_valid_mobilizer_polling_station_application(): void
    {
        $result = $this->validator->validate(
            $this->contestId,
            ElectoralFieldRole::MOBILIZER,
            'polling_station',
            $this->geography->polling_station_id,
            1
        );

        $this->assertSame('mobilizer', $result['requested_role']);
        $this->assertSame(
            (int) $this->geography->ward_id,
            $result['ward_id']
        );
    }

    public function test_rejects_mobilizer_outside_contest_ward(): void
    {
        $otherWard = DB::table('electoral_wards')
            ->where('id', '!=', $this->geography->ward_id)
            ->value('id');

        $this->assertNotNull($otherWard);

        $this->expectException(ValidationException::class);

        $this->validator->validate(
            $this->contestId,
            ElectoralFieldRole::MOBILIZER,
            'ward',
            (int) $otherWard,
            1
        );
    }

    public function test_rejects_mobilizer_county_scope_for_mca(): void
    {
        $this->expectException(ValidationException::class);

        $this->validator->validate(
            $this->contestId,
            ElectoralFieldRole::MOBILIZER,
            'county',
            $this->geography->county_id,
            1
        );
    }

    public function test_rejects_mobilizer_national_scope(): void
    {
        $this->expectException(ValidationException::class);

        $this->validator->validate(
            $this->contestId,
            ElectoralFieldRole::MOBILIZER,
            'national',
            null,
            1
        );
    }

}
