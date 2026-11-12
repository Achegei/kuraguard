<?php

namespace Tests\Feature;

use App\Enums\ElectoralOffice;
use App\Services\ElectoralJurisdictionValidator;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use Tests\TestCase;

class ElectoralJurisdictionValidatorTest extends TestCase
{
    private ElectoralJurisdictionValidator $validator;

    protected function setUp(): void
    {
        parent::setUp();
        $this->validator = app(ElectoralJurisdictionValidator::class);
    }

    private function geography(): object
    {
        $record = DB::table('electoral_polling_stations as ps')
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

        $this->assertNotNull($record, 'Test geography must be seeded.');

        return $record;
    }

    public function test_presidential_contest_is_national(): void
    {
        $result = $this->validator->validateContest(
            ElectoralOffice::PRESIDENT, null, null, null
        );

        $this->assertSame('national', $result['level']);
    }

    public function test_county_constituency_and_ward_contests(): void
    {
        $g = $this->geography();

        $this->assertSame(
            'county',
            $this->validator->validateContest(
                ElectoralOffice::GOVERNOR, $g->county_id, null, null
            )['level']
        );

        $this->assertSame(
            'constituency',
            $this->validator->validateContest(
                ElectoralOffice::MP,
                $g->county_id,
                $g->constituency_id,
                null
            )['level']
        );

        $this->assertSame(
            'ward',
            $this->validator->validateContest(
                ElectoralOffice::MCA,
                $g->county_id,
                $g->constituency_id,
                $g->ward_id
            )['level']
        );
    }

    public function test_president_rejects_county_selection(): void
    {
        $this->expectException(ValidationException::class);

        $this->validator->validateContest(
            ElectoralOffice::PRESIDENT, $this->geography()->county_id, null, null
        );
    }

    public function test_mp_rejects_mismatched_county(): void
    {
        $g = $this->geography();

        $otherCounty = DB::table('electoral_counties')
            ->where('id', '!=', $g->county_id)
            ->value('id');

        $this->assertNotNull($otherCounty);

        $this->expectException(ValidationException::class);

        $this->validator->validateContest(
            ElectoralOffice::MP,
            (int) $otherCounty,
            $g->constituency_id,
            null
        );
    }

    public function test_polling_station_within_mca_jurisdiction(): void
    {
        $g = $this->geography();

        $result = $this->validator->validateApplication(
            ElectoralOffice::MCA,
            [
                'county_id' => $g->county_id,
                'constituency_id' => $g->constituency_id,
                'ward_id' => $g->ward_id,
            ],
            'polling_station',
            $g->polling_station_id
        );

        $this->assertSame(
            (int) $g->ward_id,
            $result['ward_id']
        );
    }

    public function test_mca_rejects_station_outside_ward(): void
    {
        $g = $this->geography();

        $otherStation = DB::table('electoral_polling_stations as ps')
            ->join('electoral_polling_centres as pc', 'pc.id', '=', 'ps.polling_centre_id')
            ->where('pc.ward_id', '!=', $g->ward_id)
            ->value('ps.id');

        $this->assertNotNull($otherStation);

        $this->expectException(ValidationException::class);

        $this->validator->validateApplication(
            ElectoralOffice::MCA,
            [
                'county_id' => $g->county_id,
                'constituency_id' => $g->constituency_id,
                'ward_id' => $g->ward_id,
            ],
            'polling_station',
            (int) $otherStation
        );
    }

    public function test_special_constituency_cannot_be_used_for_ordinary_mp_contest(): void
    {
        $specialId = DB::table('electoral_constituencies')
            ->whereNotNull('special_category_id')
            ->value('id');

        $this->assertNotNull($specialId);

        $this->expectException(ValidationException::class);

        $this->validator->validateContest(
            ElectoralOffice::MP,
            null,
            (int) $specialId,
            null
        );
    }

    public function test_mca_cannot_accept_national_application(): void
    {
        $g = $this->geography();

        $this->expectException(ValidationException::class);

        $this->validator->validateApplication(
            ElectoralOffice::MCA,
            [
                'county_id' => $g->county_id,
                'constituency_id' => $g->constituency_id,
                'ward_id' => $g->ward_id,
            ],
            'national',
            null
        );
    }

    public function test_mca_rejects_county_wide_application(): void
    {
        $g = $this->geography();

        $this->expectException(ValidationException::class);

        $this->validator->validateApplication(
            ElectoralOffice::MCA,
            [
                'county_id' => $g->county_id,
                'constituency_id' => $g->constituency_id,
                'ward_id' => $g->ward_id,
            ],
            'county',
            $g->county_id
        );
    }

    public function test_mp_rejects_county_wide_application(): void
    {
        $g = $this->geography();

        $this->expectException(ValidationException::class);

        $this->validator->validateApplication(
            ElectoralOffice::MP,
            [
                'county_id' => $g->county_id,
                'constituency_id' => $g->constituency_id,
                'ward_id' => null,
            ],
            'county',
            $g->county_id
        );
    }

    public function test_governor_accepts_county_wide_application(): void
    {
        $g = $this->geography();

        $result = $this->validator->validateApplication(
            ElectoralOffice::GOVERNOR,
            [
                'county_id' => $g->county_id,
                'constituency_id' => null,
                'ward_id' => null,
            ],
            'county',
            $g->county_id
        );

        $this->assertSame('county', $result['level']);
        $this->assertSame(
            (int) $g->county_id,
            $result['county_id']
        );
    }

}
