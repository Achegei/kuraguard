<?php

namespace App\Services;

use App\Enums\ElectoralOffice;
use Illuminate\Database\UniqueConstraintViolationException;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class AspirantOnboardingService
{
    public function __construct(
        private readonly ElectoralJurisdictionValidator $jurisdiction
    ) {
    }

    public function createContest(
        int $userId,
        string $displayName,
        int $electionYear,
        ElectoralOffice $office,
        ?int $countyId = null,
        ?int $constituencyId = null,
        ?int $wardId = null,
        ?int $datasetId = null
    ): int {
        $displayName = trim($displayName);

        if ($displayName === '' || mb_strlen($displayName) > 150) {
            $this->fail(
                'display_name',
                'A valid aspirant display name is required.'
            );
        }

        if ($electionYear < 2022 || $electionYear > 2100) {
            $this->fail(
                'election_year',
                'A valid election year is required.'
            );
        }

        $geography = $this->jurisdiction->validateContest(
            $office,
            $countyId,
            $constituencyId,
            $wardId
        );

        try {
            return DB::transaction(function () use (
            $userId,
            $displayName,
            $electionYear,
            $office,
            $geography,
            $datasetId
        ): int {
            // Serialize onboarding for the same user on databases
            // supporting SELECT ... FOR UPDATE.
            // SQLite does not provide equivalent row-level locking.
            $user = DB::table('users')
                ->where('id', $userId)
                ->lockForUpdate()
                ->first();

            if (! $user) {
                $this->fail('user_id', 'User account not found.');
            }

            $dataset = $datasetId === null
                ? DB::table('electoral_datasets')
                    ->where('reference_year', 2022)
                    ->where('status', 'historical')
                    ->orderByDesc('id')
                    ->first()
                : DB::table('electoral_datasets')
                    ->where('id', $datasetId)
                    ->first();

            if (! $dataset) {
                $this->fail(
                    'electoral_dataset_id',
                    'Electoral reference dataset not found.'
                );
            }

            $aspirant = DB::table('aspirants')
                ->where('user_id', $userId)
                ->first();

            if (! $aspirant) {
                $aspirantId = DB::table('aspirants')->insertGetId([
                    'user_id' => $userId,
                    'display_name' => $displayName,
                    'verification_status' => 'self_declared',
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
            } else {
                $aspirantId = (int) $aspirant->id;
            }

            $jurisdiction = match ($office) {
                ElectoralOffice::PRESIDENT => 'national',

                ElectoralOffice::GOVERNOR,
                ElectoralOffice::SENATOR,
                ElectoralOffice::WOMAN_REPRESENTATIVE =>
                    'county:' . $geography['county_id'],

                ElectoralOffice::MP =>
                    'constituency:' . $geography['constituency_id'],

                ElectoralOffice::MCA =>
                    'ward:' . $geography['ward_id'],
            };

            $identityKey = implode(':', [
                'aspirant',
                $aspirantId,
                $office->value,
                $electionYear,
                $jurisdiction,
            ]);

            $duplicate = DB::table('aspirant_contests')
                ->where('aspirant_id', $aspirantId)
                ->where('office', $office->value)
                ->where('election_year', $electionYear)
                ->where('county_id', $geography['county_id'])
                ->where('constituency_id', $geography['constituency_id'])
                ->where('ward_id', $geography['ward_id'])
                ->exists();

            if ($duplicate) {
                $this->fail(
                    'contest',
                    'This aspirant contest already exists.'
                );
            }

            return DB::table('aspirant_contests')->insertGetId([
                'aspirant_id' => $aspirantId,
                'electoral_dataset_id' => $dataset->id,
                'contest_identity_key' => $identityKey,
                'election_year' => $electionYear,
                'office' => $office->value,
                'county_id' => $geography['county_id'],
                'constituency_id' => $geography['constituency_id'],
                'ward_id' => $geography['ward_id'],
                'declaration_status' => 'self_declared',
                'created_at' => now(),
                'updated_at' => now(),
            ]);
            }, 3);
        } catch (UniqueConstraintViolationException $exception) {
            $aspirantId = DB::table('aspirants')
                ->where('user_id', $userId)
                ->value('id');

            $duplicate = $aspirantId !== null
                && DB::table('aspirant_contests')
                    ->where('aspirant_id', $aspirantId)
                    ->where('office', $office->value)
                    ->where('election_year', $electionYear)
                    ->where('county_id', $geography['county_id'])
                    ->where('constituency_id', $geography['constituency_id'])
                    ->where('ward_id', $geography['ward_id'])
                    ->exists();

            if ($duplicate) {
                $this->fail(
                    'contest',
                    'This aspirant contest already exists.'
                );
            }

            throw $exception;
        }
    }

    private function fail(string $field, string $message): never
    {
        throw ValidationException::withMessages([
            $field => $message,
        ]);
    }
}
