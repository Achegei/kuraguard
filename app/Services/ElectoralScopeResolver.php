<?php

namespace App\Services;

use App\Enums\ElectoralOffice;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class ElectoralScopeResolver
{
    public function resolve(
        ElectoralOffice $office,
        ?int $geographicId = null,
        ?int $datasetId = null
    ): array {
        $level = $office->scopeLevel();

        if ($level !== 'national' && (
            $geographicId === null || $geographicId < 1
        )) {
            throw ValidationException::withMessages([
                'geographic_id' => 'A valid geographic area is required.',
            ]);
        }

        if ($level === 'national' && $geographicId !== null) {
            throw ValidationException::withMessages([
                'geographic_id' => 'National scope does not accept a geographic ID.',
            ]);
        }

        $dataset = $datasetId === null
            ? DB::table('electoral_datasets')
                ->where('reference_year', 2022)
                ->where('status', 'historical')
                ->first()
            : DB::table('electoral_datasets')
                ->where('id', $datasetId)
                ->first();

        if (! $dataset) {
            throw ValidationException::withMessages([
                'dataset_id' => 'Electoral reference dataset not found.',
            ]);
        }

        $table = match ($level) {
            'county' => 'electoral_counties',
            'constituency' => 'electoral_constituencies',
            'ward' => 'electoral_wards',
            default => null,
        };

        $area = $table === null
            ? null
            : DB::table($table)->where('id', $geographicId)->first();

        if ($table !== null && ! $area) {
            throw ValidationException::withMessages([
                'geographic_id' => 'Selected electoral area was not found.',
            ]);
        }

        $stations = DB::table('electoral_polling_stations as ps')
            ->join(
                'electoral_polling_centres as pc',
                'pc.id',
                '=',
                'ps.polling_centre_id'
            )
            ->join(
                'electoral_wards as w',
                'w.id',
                '=',
                'pc.ward_id'
            )
            ->join(
                'electoral_constituencies as co',
                'co.id',
                '=',
                'w.constituency_id'
            )
            ->leftJoin(
                'electoral_counties as c',
                'c.id',
                '=',
                'co.county_id'
            )
            ->join(
                'electoral_register_snapshots as rs',
                function ($join) use ($dataset) {
                    $join->on(
                        'rs.polling_station_id',
                        '=',
                        'ps.id'
                    )->where('rs.dataset_id', '=', $dataset->id);
                }
            );

        match ($level) {
            'county' => $stations->where('c.id', $geographicId),
            'constituency' => $stations->where('co.id', $geographicId),
            'ward' => $stations->where('w.id', $geographicId),
            default => null,
        };

        // Ordinary geographic jurisdictions only.
        // Special-category records require separate scope rules.
        $stations->whereNotNull('co.county_id');

        $summary = $stations
            ->selectRaw('COUNT(DISTINCT ps.id) AS polling_stations')
            ->selectRaw('COUNT(DISTINCT pc.id) AS polling_centres')
            ->selectRaw('COALESCE(SUM(rs.registered_voters), 0) AS registered_voters')
            ->first();

        return [
            'office' => $office->value,
            'office_label' => $office->label(),
            'scope_level' => $level,
            'geographic_id' => $geographicId,
            'geographic_name' => $area?->name ?? 'Kenya',
            'navigation_levels' => $office->navigationLevels(),
            'dataset' => [
                'id' => $dataset->id,
                'name' => $dataset->name,
                'reference_year' => $dataset->reference_year,
                'status' => $dataset->status,
            ],
            'statistics' => [
                'polling_centres' => (int) $summary->polling_centres,
                'polling_stations' => (int) $summary->polling_stations,
                'registered_voters' => (int) $summary->registered_voters,
            ],
        ];
    }
}
