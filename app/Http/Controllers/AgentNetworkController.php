<?php

namespace App\Http\Controllers;

use App\Models\Agent;
use App\Models\AuditLog;
use App\Models\AgentAssignment;
use App\Models\PollingStation;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use App\Models\Election;
use Illuminate\View\View;

class AgentNetworkController extends Controller
{

    public function store(Request $request): RedirectResponse
    {
        // Demo-only administrative operation.
        abort_unless(
            auth()->check() && (int) auth()->id() === 1,
            403
        );

        $election = Election::query()
            ->where('is_demo', true)
            ->firstOrFail();

        $validated = $request->validate([
            'name' => ['required', 'string', 'max:150'],
            'phone' => ['nullable', 'string', 'max:30'],
            'polling_station_id' => [
                'nullable',
                'integer',
                'exists:polling_stations,id',
            ],
        ]);

        DB::transaction(function () use ($validated, $election) {
            $stationId = $validated['polling_station_id'] ?? null;

            if ($stationId !== null) {
                $station = PollingStation::query()
                    ->where('election_id', $election->id)
                    ->lockForUpdate()
                    ->findOrFail($stationId);

                $alreadyAssigned = AgentAssignment::query()
                    ->where('polling_station_id', $station->id)
                    ->exists();

                if ($alreadyAssigned) {
                    throw ValidationException::withMessages([
                        'polling_station_id' =>
                            'This polling station already has an assigned agent.',
                    ]);
                }
            }

            $agent = Agent::create([
                'election_id' => $election->id,
                'user_id' => null,
                'name' => $validated['name'],
                'phone' => $validated['phone'] ?? null,
                'status' => 'active',
            ]);

            if ($stationId !== null) {
                AgentAssignment::create([
                    'agent_id' => $agent->id,
                    'polling_station_id' => $stationId,
                    'status' => 'assigned',
                    'checked_in_at' => null,
                ]);
            }

            AuditLog::create([
                'user_id' => auth()->id(),
                'action' => 'agent.registered',
                'entity_type' => 'agent',
                'entity_id' => $agent->id,
                'metadata' => [
                    'election_id' => $election->id,
                    'polling_station_id' => $stationId,
                    'demo' => true,
                ],
            ]);
        });

        return redirect()
            ->route('agents.index')
            ->with('success', 'Agent registered successfully.');
    }


    public function assign(Request $request): RedirectResponse
    {
        abort_unless(
            $request->user() !== null
                && (int) $request->user()->id === 1,
            403
        );

        $validated = $request->validate([
            'agent_id' => ['required', 'integer', 'exists:agents,id'],
            'polling_station_id' => [
                'required',
                'integer',
                'exists:polling_stations,id',
            ],
        ]);

        $election = Election::query()
            ->where('is_demo', true)
            ->firstOrFail();

        DB::transaction(function () use ($validated, $election, $request) {
            $agent = Agent::query()
                ->where('election_id', $election->id)
                ->lockForUpdate()
                ->findOrFail($validated['agent_id']);

            $station = PollingStation::query()
                ->where('election_id', $election->id)
                ->lockForUpdate()
                ->findOrFail($validated['polling_station_id']);

            if (
                AgentAssignment::query()
                    ->where('agent_id', $agent->id)
                    ->exists()
            ) {
                throw ValidationException::withMessages([
                    'agent_id' => 'This agent is already assigned.',
                ]);
            }

            if (
                AgentAssignment::query()
                    ->where('polling_station_id', $station->id)
                    ->exists()
            ) {
                throw ValidationException::withMessages([
                    'polling_station_id' =>
                        'This polling station already has an agent.',
                ]);
            }

            $assignment = AgentAssignment::create([
                'agent_id' => $agent->id,
                'polling_station_id' => $station->id,
                'status' => 'assigned',
                'checked_in_at' => null,
            ]);

            AuditLog::create([
                'user_id' => $request->user()->id,
                'action' => 'agent.assigned',
                'entity_type' => 'agent_assignment',
                'entity_id' => $assignment->id,
                'metadata' => [
                    'agent_id' => $agent->id,
                    'polling_station_id' => $station->id,
                    'election_id' => $election->id,
                    'demo' => true,
                ],
            ]);
        });

        return redirect()
            ->route('agents.index')
            ->with('success', 'Agent assigned successfully.');
    }

    public function index(): View
    {
        $election = Election::query()
            ->where('is_demo', true)
            ->firstOrFail();

        $agents = Agent::query()
            ->where('election_id', $election->id)
            ->with('assignments.pollingStation')
            ->orderBy('name')
            ->get();

        $totalAgents = $agents->count();

        $checkedIn = $agents->filter(
            fn ($agent) => $agent->assignments->contains(
                fn ($assignment) =>
                    $assignment->status === 'checked_in'
                    || $assignment->checked_in_at !== null
            )
        )->count();

        $assigned = $agents->filter(
            fn ($agent) => $agent->assignments->isNotEmpty()
        )->count();

        return view('agents.index', compact(
            'election',
            'agents',
            'totalAgents',
            'checkedIn',
            'assigned'
        ));
    }
}
