<?php

namespace App\Http\Controllers;

use App\Services\CampaignOperationsService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;

class CampaignOperationsController extends Controller
{
    public function __construct(
        private readonly CampaignOperationsService $operations
    ) {}

    public function stationReport(
        Request $request,
        int $contestId
    ): JsonResponse {
        $data = $request->validate([
            'polling_station_id' => ['required', 'integer', 'min:1'],
            'attendance_status' => [
                'required',
                Rule::in(['pending', 'checked_in']),
            ],
            'reported_turnout' => [
                'nullable', 'integer', 'min:0', 'max:100000',
            ],
            'notes' => ['nullable', 'string', 'max:5000'],
        ]);

        $id = $this->operations->reportStation(
            $request->user(),
            $contestId,
            $data
        );

        return response()->json([
            'status' => 'success',
            'report_id' => $id,
        ]);
    }

    public function incident(
        Request $request,
        int $contestId
    ): JsonResponse {
        $data = $request->validate([
            'polling_station_id' => ['required', 'integer', 'min:1'],
            'category' => [
                'required', 'string', 'max:60',
            ],
            'severity' => [
                'required',
                Rule::in(['low', 'medium', 'high', 'critical']),
            ],
            'description' => [
                'required', 'string', 'min:5', 'max:5000',
            ],
        ]);

        $id = $this->operations->reportIncident(
            $request->user(),
            $contestId,
            $data
        );

        return response()->json([
            'status' => 'success',
            'incident_id' => $id,
        ], 201);
    }

    public function overview(
        Request $request,
        int $contestId
    ): JsonResponse {
        abort_unless(
            Gate::forUser($request->user())->allows(
                'campaign.view',
                $contestId
            ),
            403
        );

        $reports = DB::table('campaign_station_reports')
            ->where('aspirant_contest_id', $contestId)
            ->orderByDesc('updated_at')
            ->limit(100)
            ->get();

        $incidents = DB::table('campaign_incidents')
            ->where('aspirant_contest_id', $contestId)
            ->orderByDesc('created_at')
            ->limit(100)
            ->get();

        return response()->json([
            'campaign_id' => $contestId,
            'station_reports' => $reports,
            'incidents' => $incidents,
        ]);
    }
}
