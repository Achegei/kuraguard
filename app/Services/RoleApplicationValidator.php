<?php

namespace App\Services;

use App\Enums\ElectoralFieldRole;
use App\Enums\ElectoralOffice;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class RoleApplicationValidator
{
    public function __construct(
        private readonly ElectoralJurisdictionValidator $jurisdiction
    ) {
    }

    public function validate(
        int $contestId,
        ElectoralFieldRole $role,
        string $geographicLevel,
        ?int $geographicId,
        int $datasetId
    ): array {
        $contest = DB::table('aspirant_contests')
            ->where('id', $contestId)
            ->first();

        if (! $contest) {
            $this->fail(
                'aspirant_contest_id',
                'The selected aspirant contest does not exist.'
            );
        }

        if ($contest->electoral_dataset_id !== $datasetId) {
            $this->fail(
                'electoral_dataset_id',
                'The electoral dataset does not match the contest.'
            );
        }

        $office = ElectoralOffice::tryFrom($contest->office);

        if (! $office) {
            $this->fail(
                'office',
                'The contest has an unsupported electoral office.'
            );
        }

        if (
            $role !== ElectoralFieldRole::MOBILIZER
            && $role->geographicLevel() !== $geographicLevel
        ) {
            $this->fail(
                'geographic_level',
                'The requested role does not match the geographic level.'
            );
        }

        $contestGeography = [
            'county_id' => $contest->county_id,
            'constituency_id' => $contest->constituency_id,
            'ward_id' => $contest->ward_id,
        ];

        $validatedContest = $this->jurisdiction->validateContest(
            $office,
            $contestGeography['county_id'],
            $contestGeography['constituency_id'],
            $contestGeography['ward_id']
        );

        if (
            $role === ElectoralFieldRole::MOBILIZER
            && ! in_array(
                $geographicLevel,
                [
                    'county',
                    'constituency',
                    'ward',
                    'polling_centre',
                    'polling_station',
                ],
                true
            )
        ) {
            $this->fail(
                'geographic_level',
                'Mobilizers must select a supported local electoral area.'
            );
        }

        $validatedLocation = $this->jurisdiction->validateApplication(
            $office,
            $validatedContest,
            $geographicLevel,
            $geographicId
        );

        if ($geographicLevel === 'polling_station') {
            $snapshotExists = DB::table(
                'electoral_register_snapshots'
            )
                ->where(
                    'dataset_id',
                    $contest->electoral_dataset_id
                )
                ->where(
                    'polling_station_id',
                    $geographicId
                )
                ->exists();

            if (! $snapshotExists) {
                $this->fail(
                    'polling_station_id',
                    'The polling station is not represented '
                    . 'in the selected electoral dataset.'
                );
            }
        }

        return [
            'aspirant_contest_id' => $contest->id,
            'electoral_dataset_id' => $contest->electoral_dataset_id,
            'requested_role' => $role->value,
            'geographic_level' => $validatedLocation['level'],
            'county_id' => $validatedLocation['county_id'],
            'constituency_id' => $validatedLocation['constituency_id'],
            'ward_id' => $validatedLocation['ward_id'],
            'polling_centre_id' => $validatedLocation['polling_centre_id'],
            'polling_station_id' => $validatedLocation['polling_station_id'],
        ];
    }

    private function fail(
        string $field,
        string $message
    ): never {
        throw ValidationException::withMessages([
            $field => $message,
        ]);
    }
}
