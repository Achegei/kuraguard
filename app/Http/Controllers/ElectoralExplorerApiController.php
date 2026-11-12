<?php

namespace App\Http\Controllers;

use App\Enums\ElectoralOffice;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class ElectoralExplorerApiController extends Controller
{
    public function offices(): JsonResponse
    {
        $offices = array_map(
            fn (ElectoralOffice $office) => [
                'code' => $office->value,
                'name' => $office->label(),
                'scope_level' => $office->scopeLevel(),
                'navigation_levels' => $office->navigationLevels(),
                'requires_geographic_selection' =>
                    $office->requiresGeographicSelection(),
            ],
            ElectoralOffice::cases()
        );

        return response()->json([
            'data' => $offices,
        ]);
    }

    public function counties(): JsonResponse
    {
        $dataset = DB::table('electoral_datasets')
            ->where('reference_year', 2022)
            ->where('status', 'historical')
            ->orderByDesc('id')
            ->first();

        if (! $dataset) {
            abort(503, 'Historical electoral reference unavailable.');
        }

        $totals = DB::table('electoral_constituencies as co')
            ->join('electoral_wards as w', 'w.constituency_id', '=', 'co.id')
            ->join('electoral_polling_centres as pc', 'pc.ward_id', '=', 'w.id')
            ->join('electoral_polling_stations as ps', 'ps.polling_centre_id', '=', 'pc.id')
            ->join('electoral_register_snapshots as rs', function ($join) use ($dataset) {
                $join->on('rs.polling_station_id', '=', 'ps.id')
                    ->where('rs.dataset_id', '=', $dataset->id);
            })
            ->whereNotNull('co.county_id')
            ->whereNull('co.special_category_id')
            ->groupBy('co.county_id')
            ->select('co.county_id')
            ->selectRaw('COALESCE(SUM(rs.registered_voters), 0) AS registered_voters');

        $counties = DB::table('electoral_counties as c')
            ->leftJoinSub($totals, 'totals', function ($join) {
                $join->on('totals.county_id', '=', 'c.id');
            })
            ->select('c.id', 'c.code', 'c.name')
            ->selectRaw('COALESCE(totals.registered_voters, 0) AS registered_voters')
            ->orderBy('c.name')
            ->get();

        return response()->json([
            'data' => $counties,
            'reference_year' => 2022,
        ]);
    }

    public function constituencies(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'county_id' => [
                'required',
                'integer',
                'exists:electoral_counties,id',
            ],
        ]);

        $dataset = DB::table('electoral_datasets')
            ->where('reference_year', 2022)
            ->where('status', 'historical')
            ->orderByDesc('id')
            ->first();

        if (! $dataset) {
            abort(503, 'Historical electoral reference unavailable.');
        }

        $totals = DB::table('electoral_wards as w')
            ->join('electoral_polling_centres as pc', 'pc.ward_id', '=', 'w.id')
            ->join('electoral_polling_stations as ps', 'ps.polling_centre_id', '=', 'pc.id')
            ->join('electoral_register_snapshots as rs', function ($join) use ($dataset) {
                $join->on('rs.polling_station_id', '=', 'ps.id')
                    ->where('rs.dataset_id', '=', $dataset->id);
            })
            ->groupBy('w.constituency_id')
            ->select('w.constituency_id')
            ->selectRaw('COALESCE(SUM(rs.registered_voters), 0) AS registered_voters');

        $constituencies = DB::table('electoral_constituencies as co')
            ->leftJoinSub($totals, 'totals', function ($join) {
                $join->on('totals.constituency_id', '=', 'co.id');
            })
            ->where('co.county_id', $validated['county_id'])
            ->whereNull('co.special_category_id')
            ->select('co.id', 'co.code', 'co.name', 'co.county_id')
            ->selectRaw('COALESCE(totals.registered_voters, 0) AS registered_voters')
            ->orderBy('co.name')
            ->get();

        return response()->json([
            'data' => $constituencies,
            'reference_year' => 2022,
        ]);
    }

    public function wards(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'constituency_id' => [
                'required',
                'integer',
                'exists:electoral_constituencies,id',
            ],
        ]);

        $dataset = DB::table('electoral_datasets')
            ->where('reference_year', 2022)
            ->where('status', 'historical')
            ->orderByDesc('id')
            ->first();

        if (! $dataset) {
            abort(503, 'Historical electoral reference unavailable.');
        }

        $totals = DB::table('electoral_polling_centres as pc')
            ->join('electoral_polling_stations as ps', 'ps.polling_centre_id', '=', 'pc.id')
            ->join('electoral_register_snapshots as rs', function ($join) use ($dataset) {
                $join->on('rs.polling_station_id', '=', 'ps.id')
                    ->where('rs.dataset_id', '=', $dataset->id);
            })
            ->groupBy('pc.ward_id')
            ->select('pc.ward_id')
            ->selectRaw('COALESCE(SUM(rs.registered_voters), 0) AS registered_voters');

        $wards = DB::table('electoral_wards as w')
            ->join('electoral_constituencies as co', 'co.id', '=', 'w.constituency_id')
            ->leftJoinSub($totals, 'totals', function ($join) {
                $join->on('totals.ward_id', '=', 'w.id');
            })
            ->where('w.constituency_id', $validated['constituency_id'])
            ->whereNotNull('co.county_id')
            ->whereNull('co.special_category_id')
            ->select('w.id', 'w.code', 'w.name', 'w.constituency_id')
            ->selectRaw('COALESCE(totals.registered_voters, 0) AS registered_voters')
            ->orderBy('w.name')
            ->get();

        return response()->json([
            'data' => $wards,
            'reference_year' => 2022,
        ]);
    }
}
