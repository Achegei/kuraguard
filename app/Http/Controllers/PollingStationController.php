<?php

namespace App\Http\Controllers;

use App\Models\AuditLog;
use App\Models\Election;
use App\Models\PollingStation;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class PollingStationController extends Controller
{
    public function store(Request $request): RedirectResponse
    {
        abort_unless(
            $request->user() !== null
                && (int) $request->user()->id === 1,
            403
        );

        $validated = $request->validate([
            'county' => ['required', 'string', 'max:120'],
            'constituency' => ['required', 'string', 'max:120'],
            'ward' => ['required', 'string', 'max:120'],
            'name' => ['required', 'string', 'max:180'],
            'station_code' => [
                'required',
                'string',
                'max:50',
                'regex:/^[A-Za-z0-9_-]+$/',
            ],
            'registered_voters' => [
                'required',
                'integer',
                'min:0',
                'max:100000',
            ],
        ]);

        $election = Election::query()
            ->where('is_demo', true)
            ->firstOrFail();

        DB::transaction(function () use (
            $validated,
            $election,
            $request
        ) {
            $code = strtoupper(trim($validated['station_code']));

            $duplicate = PollingStation::query()
                ->where('election_id', $election->id)
                ->where('station_code', $code)
                ->exists();

            if ($duplicate) {
                throw ValidationException::withMessages([
                    'station_code' =>
                        'This station code is already registered.',
                ]);
            }

            $station = PollingStation::create([
                'election_id' => $election->id,
                'county' => trim($validated['county']),
                'constituency' => trim($validated['constituency']),
                'ward' => trim($validated['ward']),
                'name' => trim($validated['name']),
                'station_code' => $code,
                'registered_voters' =>
                    $validated['registered_voters'],
                'status' => 'pending',
            ]);

            AuditLog::create([
                'user_id' => $request->user()->id,
                'action' => 'polling_station.registered',
                'entity_type' => 'polling_station',
                'entity_id' => $station->id,
                'metadata' => [
                    'election_id' => $election->id,
                    'station_code' => $station->station_code,
                    'demo' => true,
                ],
            ]);
        });

        return redirect()
            ->route('agents.index', ['#' => 'agent-assignment'])
            ->with(
                'success',
                'Simulated polling station registered successfully.'
            );
    }
}
