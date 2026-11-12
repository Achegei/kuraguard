<?php

namespace Database\Seeders;

use App\Models\Agent;
use App\Models\AgentAssignment;
use App\Models\Candidate;
use App\Models\CandidateResult;
use App\Models\Election;
use App\Models\Incident;
use App\Models\PollingStation;
use App\Models\ResultSubmission;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use RuntimeException;

class KuraGuardDemoSeeder extends Seeder
{
    public function run(): void
    {
        DB::transaction(function () {
            $election = Election::firstOrCreate(
                ['name' => 'KuraGuard Simulated Parliamentary Election'],
                [
                    'office' => 'Member of Parliament',
                    'election_date' => '2027-08-10',
                    'status' => 'demo',
                    'is_demo' => true,
                ]
            );

            if (!$election->is_demo) {
                throw new RuntimeException('STOP: Election is not marked as demo.');
            }

            $candidateNames = [
                ['Alex Mwangi', 'Demo Alliance', true],
                ['Grace Achieng', 'People First', false],
                ['Samuel Otieno', 'Unity Movement', false],
            ];

            $candidates = [];

            foreach ($candidateNames as [$name, $party, $client]) {
                $candidates[] = Candidate::firstOrCreate(
                    ['election_id' => $election->id, 'name' => $name],
                    ['party' => $party, 'is_client' => $client]
                );
            }

            $wards = [
                'Central Ward',
                'East Ward',
                'West Ward',
                'North Ward',
            ];

            $stations = [];

            for ($i = 1; $i <= 12; $i++) {
                $code = sprintf('DEMO-%03d', $i);

                $station = PollingStation::firstOrCreate(
                    [
                        'election_id' => $election->id,
                        'station_code' => $code,
                    ],
                    [
                        'county' => 'Demo County',
                        'constituency' => 'Demo Constituency',
                        'ward' => $wards[($i - 1) % 4],
                        'name' => "Simulated Polling Station {$i}",
                        'registered_voters' => 650 + ($i * 35),
                        'status' => 'pending',
                    ]
                );

                $stations[] = $station;

                $agent = Agent::firstOrCreate(
                    [
                        'election_id' => $election->id,
                        'name' => "Demo Agent {$i}",
                    ],
                    [
                        'phone' => null,
                        'status' => 'active',
                    ]
                );

                AgentAssignment::firstOrCreate(
                    [
                        'agent_id' => $agent->id,
                        'polling_station_id' => $station->id,
                    ],
                    [
                        'status' => $i <= 10 ? 'checked_in' : 'assigned',
                        'checked_in_at' => $i <= 10 ? now() : null,
                    ]
                );

                if ($i <= 9) {
                    $votes = [
                        180 + ($i * 11),
                        145 + ($i * 7),
                        70 + ($i * 5),
                    ];

                    $submission = ResultSubmission::firstOrCreate(
                        [
                            'polling_station_id' => $station->id,
                            'notes' => 'SIMULATED DEMO DATA',
                        ],
                        [
                            'agent_id' => $agent->id,
                            'valid_votes' => array_sum($votes),
                            'rejected_votes' => 4 + ($i % 5),
                            'status' => $i <= 6 ? 'verified' : 'pending',
                            'submitted_at' => now()->subMinutes(35 - $i),
                            'verified_at' => $i <= 6 ? now() : null,
                        ]
                    );

                    foreach ($candidates as $index => $candidate) {
                        CandidateResult::firstOrCreate(
                            [
                                'result_submission_id' => $submission->id,
                                'candidate_id' => $candidate->id,
                            ],
                            ['votes' => $votes[$index]]
                        );
                    }
                }
            }

            Incident::firstOrCreate(
                [
                    'polling_station_id' => $stations[7]->id,
                    'category' => 'Result verification',
                    'description' => 'SIMULATED: Result image requires manual review.',
                ],
                [
                    'severity' => 'medium',
                    'status' => 'open',
                ]
            );

            Incident::firstOrCreate(
                [
                    'polling_station_id' => $stations[10]->id,
                    'category' => 'Agent connectivity',
                    'description' => 'SIMULATED: Agent has not checked in.',
                ],
                [
                    'severity' => 'high',
                    'status' => 'open',
                ]
            );

            $this->command?->info(
                'KuraGuard simulated demo election seeded successfully.'
            );
        });
    }
}
