<?php

namespace App\Http\Controllers;

use App\Models\Election;
use App\Models\CandidateResult;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Facades\DB;

class CommandCentreController extends Controller
{
    public function index(): View
    {
        $election = Election::query()
            ->where('is_demo', true)
            ->with(['candidates'])
            ->firstOrFail();

        $stationIds = $election->pollingStations()
            ->pluck('id');

        $stations = $election->pollingStations()
            ->with([
                'assignments.agent',
                'submissions' => function ($query) {
                    $query->latest('submitted_at');
                },
            ])
            ->orderBy('station_code')
            ->get();

        $verifiedStations = DB::table('result_submissions')
            ->whereIn('polling_station_id', $stationIds)
            ->where('status', 'verified')
            ->distinct()
            ->count('polling_station_id');

        $pendingStations = DB::table('result_submissions')
            ->whereIn('polling_station_id', $stationIds)
            ->where('status', 'pending')
            ->whereNotIn(
                'polling_station_id',
                DB::table('result_submissions')
                    ->select('polling_station_id')
                    ->where('status', 'verified')
            )
            ->distinct()
            ->count('polling_station_id');

        $reportedStations = DB::table('result_submissions')
            ->whereIn('polling_station_id', $stationIds)
            ->distinct()
            ->count('polling_station_id');

        $agentIds = $election->agents()->pluck('id');

        $checkedInAgents = DB::table('agent_assignments')
            ->whereIn('agent_id', $agentIds)
            ->whereNotNull('checked_in_at')
            ->distinct()
            ->count('agent_id');

        $candidateTotals = $election->candidates
            ->map(function ($candidate) use ($stationIds) {
                $votes = CandidateResult::query()
                    ->where('candidate_id', $candidate->id)
                    ->whereHas('submission', function ($query) use ($stationIds) {
                        $query->whereIn('polling_station_id', $stationIds)
                            ->where('status', 'verified');
                    })
                    ->sum('votes');

                return [
                    'name' => $candidate->name,
                    'party' => $candidate->party,
                    'votes' => (int) $votes,
                    'is_client' => $candidate->is_client,
                ];
            })
            ->sortByDesc('votes')
            ->values();

        $pendingSubmissions = DB::table('result_submissions')
            ->join(
                'polling_stations',
                'polling_stations.id',
                '=',
                'result_submissions.polling_station_id'
            )
            ->where('polling_stations.election_id', $election->id)
            ->where('result_submissions.status', 'pending')
            ->select(
                'result_submissions.id',
                'result_submissions.valid_votes',
                'result_submissions.submitted_at',
                'polling_stations.name as station_name',
                'polling_stations.station_code'
            )
            ->orderByDesc('result_submissions.submitted_at')
            ->get();

        $incidents = DB::table('incidents')
            ->join(
                'polling_stations',
                'polling_stations.id',
                '=',
                'incidents.polling_station_id'
            )
            ->where('polling_stations.election_id', $election->id)
            ->select(
                'incidents.id',
                'incidents.category',
                'incidents.severity',
                'incidents.status',
                'polling_stations.name as station_name'
            )
            ->orderByDesc('incidents.id')
            ->get();

        $metrics = [
            'stations' => $stations->count(),
            'verified' => $verifiedStations,
            'pending' => $pendingStations,
            'unreported' => $stations->filter(
                fn ($station) => $station->submissions->isEmpty()
            )->count(),
            'rejected' => $stations->filter(
                fn ($station) =>
                    $station->submissions->contains('status', 'rejected')
                    && !$station->submissions->contains('status', 'verified')
                    && !$station->submissions->contains('status', 'pending')
            )->count(),
            'agents' => $election->agents()->count(),
            'checked_in' => $checkedInAgents,
            'incidents' => $incidents->where('status', 'open')->count(),
            'verified_votes' => $candidateTotals->sum('votes'),
        ];

        return view('command-centre.index', compact(
            'election',
            'stations',
            'candidateTotals',
            'pendingSubmissions',
            'incidents',
            'metrics'
        ));
    }
}
