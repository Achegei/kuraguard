<?php

namespace App\Http\Controllers;

use App\Enums\ElectoralOffice;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class CampaignScenarioGeographyController extends Controller
{
    /**
     * Returns a single bounded level of ordinary electoral geography.
     * The office, jurisdiction and dataset always come from the campaign.
     */
    public function index(Request $request, int $contestId): JsonResponse
    {
        $input = $request->validate([
            'level' => ['required', 'in:county,constituency,ward,polling_centre'],
            'parent_id' => ['nullable', 'integer', 'min:1'],
            'page' => ['sometimes', 'integer', 'min:1', 'max:100000'],
        ]);

        $contest = DB::table('aspirant_contests')->where('id', $contestId)->first();
        abort_if($contest === null, 404);
        $office = ElectoralOffice::from($contest->office);
        $scope = $office->scopeLevel();
        $level = $input['level'];
        $parentId = isset($input['parent_id']) ? (int) $input['parent_id'] : null;
        $page = (int) ($input['page'] ?? 1);
        $levels = ['county', 'constituency', 'ward', 'polling_centre'];
        $first = match ($scope) {
            'national' => 'county',
            'county' => 'constituency',
            'constituency' => 'ward',
            'ward' => 'polling_centre',
        };
        $firstIndex = array_search($first, $levels, true);
        $levelIndex = array_search($level, $levels, true);
        if ($levelIndex < $firstIndex) {
            throw ValidationException::withMessages(['level' => 'Level is outside this campaign scope.']);
        }
        $expectedParent = match ($level) {
            'county' => null,
            'constituency' => $scope === 'county' ? (int) $contest->county_id : null,
            'ward' => $scope === 'constituency' ? (int) $contest->constituency_id : null,
            'polling_centre' => $scope === 'ward' ? (int) $contest->ward_id : null,
        };
        if ($levelIndex === $firstIndex) {
            if ($parentId !== null && $parentId !== $expectedParent) {
                throw ValidationException::withMessages(['parent_id' => 'Parent does not match the campaign jurisdiction.']);
            }
            $parentId = $expectedParent;
        } elseif ($parentId === null) {
            throw ValidationException::withMessages(['parent_id' => 'A geographic parent is required.']);
        }

        // Every request validates the full parent chain, not only the immediate parent.
        $parent = $this->parentScope($level, $parentId, $scope, $contest);
        $dataset = DB::table('electoral_datasets')->where('id', $contest->electoral_dataset_id)->first();
        abort_if($dataset === null, 503, 'Campaign electoral dataset unavailable.');

        $query = match ($level) {
            'county' => DB::table('electoral_counties as area')
                ->select('area.id', 'area.code', 'area.name'),
            'constituency' => DB::table('electoral_constituencies as area')
                ->where('area.county_id', $parentId)
                ->whereNull('area.special_category_id')
                ->select('area.id', 'area.code', 'area.name'),
            'ward' => DB::table('electoral_wards as area')
                ->where('area.constituency_id', $parentId)
                ->select('area.id', 'area.code', 'area.name'),
            'polling_centre' => DB::table('electoral_polling_centres as area')
                ->where('area.ward_id', $parentId)
                ->select('area.id', 'area.code', 'area.name'),
        };

        $paginator = $query->orderBy('area.name')->orderBy('area.id')->paginate(100, ['*'], 'page', $page);
        $rows = collect($paginator->items());
        $ids = $rows->pluck('id')->map(fn ($id) => (int) $id)->all();
        $statistics = $this->statistics($level, $ids, (int) $dataset->id);
        $data = $rows->map(function ($row) use ($statistics) {
            $s = $statistics[(int) $row->id] ?? null;
            $stations = (int) ($s->polling_stations ?? 0);
            $covered = (int) ($s->stations_with_snapshot ?? 0);
            return [
                'id' => (int) $row->id,
                'code' => $row->code,
                'name' => $row->name,
                'polling_stations' => $stations,
                'registered_voters' => (int) ($s->registered_voters ?? 0),
                'stations_with_snapshot' => $covered,
                'stations_missing_snapshot' => max(0, $stations - $covered),
                'register_complete' => $stations > 0 && $stations === $covered,
            ];
        })->values();

        return response()->json([
            'data' => $data,
            'meta' => [
                'contest_id' => $contestId,
                'office' => $office->value,
                'scope_level' => $scope,
                'level' => $level,
                'parent_id' => $parentId,
                'dataset_id' => (int) $dataset->id,
                'reference_year' => (int) $dataset->reference_year,
                'classification' => 'ordinary_geography',
                'historical_reference_only' => true,
                'page' => $paginator->currentPage(),
                'per_page' => $paginator->perPage(),
                'total' => $paginator->total(),
                'last_page' => $paginator->lastPage(),
                'has_more_pages' => $paginator->hasMorePages(),
            ],
        ]);
    }

    private function parentScope(string $level, ?int $parentId, string $scope, object $contest): ?object
    {
        if ($level === 'county') {
            return null;
        }
        $parent = match ($level) {
            'constituency' => DB::table('electoral_counties')->where('id', $parentId)
                ->select('id')->first(),
            'ward' => DB::table('electoral_constituencies')
                ->where('id', $parentId)->whereNotNull('county_id')
                ->whereNull('special_category_id')->select('id', 'county_id')->first(),
            'polling_centre' => DB::table('electoral_wards as w')
                ->join('electoral_constituencies as co', 'co.id', '=', 'w.constituency_id')
                ->where('w.id', $parentId)->whereNotNull('co.county_id')
                ->whereNull('co.special_category_id')
                ->select('w.id', 'w.constituency_id', 'co.county_id')->first(),
        };
        if ($parent === null) {
            throw ValidationException::withMessages(['parent_id' => 'Invalid ordinary geographic parent.']);
        }
        $countyId = match ($level) {
            'constituency' => (int) $parent->id,
            'ward', 'polling_centre' => (int) $parent->county_id,
        };
        $constituencyId = match ($level) {
            'polling_centre' => (int) $parent->constituency_id,
            'ward' => (int) $parent->id,
            default => null,
        };
        if ($scope === 'county' && $countyId !== (int) $contest->county_id) {
            throw ValidationException::withMessages(['parent_id' => 'Area is outside the campaign county.']);
        }
        if ($scope === 'constituency' && $constituencyId !== (int) $contest->constituency_id) {
            throw ValidationException::withMessages(['parent_id' => 'Area is outside the campaign constituency.']);
        }
        if ($scope === 'ward' && ($level !== 'polling_centre' || (int) $parent->id !== (int) $contest->ward_id)) {
            throw ValidationException::withMessages(['parent_id' => 'Area is outside the campaign ward.']);
        }
        return $parent;
    }

    /** @return array<int, object> */
    private function statistics(string $level, array $ids, int $datasetId): array
    {
        if ($ids === []) {
            return [];
        }
        $query = DB::table('electoral_polling_stations as ps')
            ->join('electoral_polling_centres as pc', 'pc.id', '=', 'ps.polling_centre_id')
            ->join('electoral_wards as w', 'w.id', '=', 'pc.ward_id')
            ->join('electoral_constituencies as co', 'co.id', '=', 'w.constituency_id')
            ->whereNotNull('co.county_id')
            ->whereNull('co.special_category_id')
            ->leftJoin('electoral_register_snapshots as rs', function ($join) use ($datasetId) {
                $join->on('rs.polling_station_id', '=', 'ps.id')
                    ->where('rs.dataset_id', '=', $datasetId);
            });
        $column = match ($level) {
            'county' => 'co.county_id',
            'constituency' => 'co.id',
            'ward' => 'w.id',
            'polling_centre' => 'pc.id',
        };
        return $query->whereIn($column, $ids)
            ->groupBy($column)
            ->selectRaw($column.' AS area_id')
            ->selectRaw('COUNT(DISTINCT ps.id) AS polling_stations')
            ->selectRaw('COUNT(DISTINCT rs.polling_station_id) AS stations_with_snapshot')
            ->selectRaw('COALESCE(SUM(rs.registered_voters), 0) AS registered_voters')
            ->get()->keyBy('area_id')->all();
    }
}
