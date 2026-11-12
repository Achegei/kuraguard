<?php

namespace App\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class ElectoralDrilldownController extends Controller
{
    public function pollingCentres(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'ward_id' => ['required', 'integer', 'min:1'],
        ]);

        $wardId = (int) $validated['ward_id'];

        $ward = DB::table('electoral_wards as w')
            ->join(
                'electoral_constituencies as co',
                'co.id',
                '=',
                'w.constituency_id'
            )
            ->where('w.id', $wardId)
            ->whereNotNull('co.county_id')
            ->whereNull('co.special_category_id')
            ->select('w.id', 'w.name')
            ->first();

        if (!$ward) {
            throw ValidationException::withMessages([
                'ward_id' => [
                    'The selected ordinary electoral ward is invalid.',
                ],
            ]);
        }

        $dataset = DB::table('electoral_datasets')
            ->where('reference_year', 2022)
            ->where('status', 'historical')
            ->orderByDesc('id')
            ->first();

        if (!$dataset) {
            abort(503, 'Historical electoral reference unavailable.');
        }

        $centres = DB::table('electoral_polling_centres as pc')
            ->leftJoin(
                'electoral_polling_stations as ps',
                'ps.polling_centre_id',
                '=',
                'pc.id'
            )
            ->leftJoin(
                'electoral_register_snapshots as rs',
                function ($join) use ($dataset) {
                    $join->on(
                        'rs.polling_station_id',
                        '=',
                        'ps.id'
                    )->where('rs.dataset_id', '=', $dataset->id);
                }
            )
            ->where('pc.ward_id', $wardId)
            ->groupBy(
                'pc.id',
                'pc.code',
                'pc.name',
                'pc.ward_id'
            )
            ->select(
                'pc.id',
                'pc.code',
                'pc.name',
                'pc.ward_id'
            )
            ->selectRaw(
                'COUNT(DISTINCT ps.id) AS polling_stations'
            )
            ->selectRaw(
                'COALESCE(SUM(rs.registered_voters), 0) AS registered_voters'
            )
            ->orderBy('pc.name')
            ->get();

        return response()->json([
            'data' => $centres,
            'meta' => [
                'ward_id' => $ward->id,
                'ward_name' => $ward->name,
                'dataset_id' => $dataset->id,
                'reference_year' => 2022,
                'classification' => 'ordinary_geography',
                'notice' => 'Historical IEBC 2022 reference only.',
            ],
        ]);
    }

    public function pollingStations(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'polling_centre_id' => [
                'required',
                'integer',
                'min:1',
            ],
        ]);

        $centreId = (int) $validated['polling_centre_id'];

        $centre = DB::table('electoral_polling_centres as pc')
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
            ->where('pc.id', $centreId)
            ->whereNotNull('co.county_id')
            ->whereNull('co.special_category_id')
            ->select(
                'pc.id',
                'pc.code',
                'pc.name',
                'pc.ward_id'
            )
            ->first();

        if (!$centre) {
            throw ValidationException::withMessages([
                'polling_centre_id' => [
                    'The selected ordinary polling centre is invalid.',
                ],
            ]);
        }

        $dataset = DB::table('electoral_datasets')
            ->where('reference_year', 2022)
            ->where('status', 'historical')
            ->orderByDesc('id')
            ->first();

        if (!$dataset) {
            abort(503, 'Historical electoral reference unavailable.');
        }

        $stations = DB::table('electoral_polling_stations as ps')
            ->leftJoin(
                'electoral_register_snapshots as rs',
                function ($join) use ($dataset) {
                    $join->on(
                        'rs.polling_station_id',
                        '=',
                        'ps.id'
                    )->where(
                        'rs.dataset_id',
                        '=',
                        $dataset->id
                    );
                }
            )
            ->where('ps.polling_centre_id', $centreId)
            ->select(
                'ps.id',
                'ps.code',
                'ps.name',
                'ps.stream_number',
                'ps.polling_centre_id',
                'rs.registered_voters'
            )
            ->orderBy('ps.stream_number')
            ->orderBy('ps.code')
            ->get();

        return response()->json([
            'data' => $stations,
            'meta' => [
                'polling_centre_id' => $centre->id,
                'polling_centre_name' => $centre->name,
                'ward_id' => $centre->ward_id,
                'dataset_id' => $dataset->id,
                'reference_year' => 2022,
                'classification' => 'ordinary_geography',
                'notice' => 'Historical IEBC 2022 reference only.',
            ],
        ]);
    }

}
