<?php

namespace App\Services;

use App\Enums\ElectoralOffice;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class ElectoralJurisdictionValidator
{
    private const LEVELS = [
        'national',
        'county',
        'constituency',
        'ward',
        'polling_centre',
        'polling_station',
    ];

    public function validateContest(
        ElectoralOffice $office,
        ?int $countyId,
        ?int $constituencyId,
        ?int $wardId
    ): array {
        $level = $office->scopeLevel();

        $ids = [
            'county' => $countyId,
            'constituency' => $constituencyId,
            'ward' => $wardId,
        ];

        if ($level === 'national') {
            if (array_filter($ids, fn ($id) => $id !== null)) {
                $this->fail('geography', 'National contests must not specify a local jurisdiction.');
            }

            return [
                'level' => 'national',
                'county_id' => null,
                'constituency_id' => null,
                'ward_id' => null,
            ];
        }

        $targetId = $ids[$level];

        if ($targetId === null || $targetId < 1) {
            $this->fail('geography', 'A valid electoral jurisdiction is required.');
        }

        $ancestry = $this->resolveAncestry($level, $targetId);

        foreach ($ids as $key => $id) {
            if ($id !== null && $id !== $ancestry[$key]) {
                $this->fail($key . '_id', 'The selected electoral areas do not belong to the same jurisdiction.');
            }
        }

        return [
            'level' => $level,
            'county_id' => $ancestry['county'],
            'constituency_id' => $level === 'county'
                ? null : $ancestry['constituency'],
            'ward_id' => $level === 'ward'
                ? $ancestry['ward'] : null,
        ];
    }

    public function validateApplication(
        ElectoralOffice $office,
        array $contest,
        string $level,
        ?int $geographicId
    ): array {
        if (! in_array($level, self::LEVELS, true)) {
            $this->fail('geographic_level', 'Unsupported geographic level.');
        }

        $allowed = $office->navigationLevels();

        if (! in_array($level, $allowed, true)) {
            $this->fail('geographic_level', 'This geographic level is outside the contest scope.');
        }

        if ($level === 'national') {
            if ($geographicId !== null) {
                $this->fail('geographic_id', 'National selection must not have a geographic ID.');
            }

            return [
                'level' => 'national',
                'county_id' => null,
                'constituency_id' => null,
                'ward_id' => null,
                'polling_centre_id' => null,
                'polling_station_id' => null,
            ];
        }

        if ($geographicId === null || $geographicId < 1) {
            $this->fail('geographic_id', 'A valid geographic location is required.');
        }

        $ancestry = $this->resolveAncestry($level, $geographicId);

        foreach (['county', 'constituency', 'ward'] as $key) {
            $contestId = $contest[$key . '_id'] ?? null;

            if ($contestId !== null && $ancestry[$key] !== (int) $contestId) {
                $this->fail('geographic_id', 'Location is outside the aspirant jurisdiction.');
            }
        }

        return [
            'level' => $level,
            'county_id' => $ancestry['county'],
            'constituency_id' => $ancestry['constituency'],
            'ward_id' => $ancestry['ward'],
            'polling_centre_id' => $ancestry['polling_centre'],
            'polling_station_id' => $ancestry['polling_station'],
        ];
    }

    private function resolveAncestry(string $level, int $id): array
    {
        $record = match ($level) {
            'county' => DB::table('electoral_counties as c')
                ->where('c.id', $id)
                ->select('c.id as county')
                ->first(),

            'constituency' => DB::table('electoral_constituencies as co')
                ->join('electoral_counties as c', 'c.id', '=', 'co.county_id')
                ->where('co.id', $id)
                ->select('c.id as county', 'co.id as constituency')
                ->first(),

            'ward' => DB::table('electoral_wards as w')
                ->join('electoral_constituencies as co', 'co.id', '=', 'w.constituency_id')
                ->join('electoral_counties as c', 'c.id', '=', 'co.county_id')
                ->where('w.id', $id)
                ->select('c.id as county', 'co.id as constituency', 'w.id as ward')
                ->first(),

            'polling_centre' => DB::table('electoral_polling_centres as pc')
                ->join('electoral_wards as w', 'w.id', '=', 'pc.ward_id')
                ->join('electoral_constituencies as co', 'co.id', '=', 'w.constituency_id')
                ->join('electoral_counties as c', 'c.id', '=', 'co.county_id')
                ->where('pc.id', $id)
                ->select(
                    'c.id as county',
                    'co.id as constituency',
                    'w.id as ward',
                    'pc.id as polling_centre'
                )
                ->first(),

            'polling_station' => DB::table('electoral_polling_stations as ps')
                ->join('electoral_polling_centres as pc', 'pc.id', '=', 'ps.polling_centre_id')
                ->join('electoral_wards as w', 'w.id', '=', 'pc.ward_id')
                ->join('electoral_constituencies as co', 'co.id', '=', 'w.constituency_id')
                ->join('electoral_counties as c', 'c.id', '=', 'co.county_id')
                ->where('ps.id', $id)
                ->select(
                    'c.id as county',
                    'co.id as constituency',
                    'w.id as ward',
                    'pc.id as polling_centre',
                    'ps.id as polling_station'
                )
                ->first(),

            default => null,
        };

        if (! $record) {
            $this->fail(
                'geographic_id',
                'Location not found in an ordinary electoral jurisdiction.'
            );
        }

        return [
            'county' => (int) $record->county,
            'constituency' => isset($record->constituency)
                ? (int) $record->constituency : null,
            'ward' => isset($record->ward)
                ? (int) $record->ward : null,
            'polling_centre' => isset($record->polling_centre)
                ? (int) $record->polling_centre : null,
            'polling_station' => isset($record->polling_station)
                ? (int) $record->polling_station : null,
        ];
    }

    private function fail(string $field, string $message): never
    {
        throw ValidationException::withMessages([
            $field => $message,
        ]);
    }
}
