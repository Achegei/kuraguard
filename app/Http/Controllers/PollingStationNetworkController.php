<?php

namespace App\Http\Controllers;

use App\Models\Election;
use Illuminate\Contracts\View\View;

class PollingStationNetworkController extends Controller
{
    public function index(): View
    {
        $election = Election::query()
            ->where('is_demo', true)
            ->firstOrFail();

        $stations = $election->pollingStations()
            ->with([
                'assignments.agent',
                'submissions',
                'incidents',
            ])
            ->orderBy('station_code')
            ->get();

        $stations->each(function ($station) {
            $submissions = $station->submissions;

            $station->reporting_status =
                $submissions->contains('status', 'verified')
                    ? 'verified'
                    : (
                        $submissions->contains('status', 'pending')
                            ? 'pending'
                            : (
                                $submissions->contains('status', 'rejected')
                                    ? 'rejected'
                                    : 'unreported'
                            )
                    );

            $station->assigned_agent =
                $station->assignments->first()?->agent;

            $station->agent_checked_in =
                $station->assignments->contains(
                    fn ($assignment) =>
                        $assignment->checked_in_at !== null
                );

            $station->open_incidents =
                $station->incidents
                    ->where('status', 'open')
                    ->count();
        });

        $metrics = [
            'total' => $stations->count(),
            'verified' => $stations
                ->where('reporting_status', 'verified')->count(),
            'pending' => $stations
                ->where('reporting_status', 'pending')->count(),
            'rejected' => $stations
                ->where('reporting_status', 'rejected')->count(),
            'unreported' => $stations
                ->where('reporting_status', 'unreported')->count(),
            'assigned' => $stations
                ->filter(fn ($station) =>
                    $station->assignments->isNotEmpty()
                )->count(),
            'checked_in' => $stations
                ->where('agent_checked_in', true)->count(),
        ];

        return view('polling-stations.index', compact(
            'election',
            'stations',
            'metrics'
        ));
    }
}
