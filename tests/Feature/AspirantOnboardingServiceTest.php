<?php

namespace Tests\Feature;

use App\Enums\ElectoralOffice;
use App\Models\User;
use App\Services\AspirantOnboardingService;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use Tests\TestCase;

class AspirantOnboardingServiceTest extends TestCase
{
    use DatabaseTransactions;

    private function service(): AspirantOnboardingService
    {
        return app(AspirantOnboardingService::class);
    }

    private function createUser(): User
    {
        return User::factory()->create();
    }

    private function datasetId(): int
    {
        return DB::table('electoral_datasets')->insertGetId([
            'name' => 'Onboarding Test Dataset',
            'version' => 'onboarding-test-' . uniqid(),
            'source_organization' => 'Test Fixture',
            'reference_year' => 2022,
            'status' => 'historical',
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    private function countyId(): int
    {
        return DB::table('electoral_counties')->insertGetId([
            'code' => 'ONB-' . uniqid(),
            'name' => 'Onboarding Test County',
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    public function test_creates_national_aspirant_contest(): void
    {
        $user = $this->createUser();
        $datasetId = $this->datasetId();

        $contestId = $this->service()->createContest(
            $user->id,
            'Test Presidential Aspirant',
            2027,
            ElectoralOffice::PRESIDENT,
            datasetId: $datasetId
        );

        $this->assertDatabaseHas('aspirants', [
            'user_id' => $user->id,
            'display_name' => 'Test Presidential Aspirant',
        ]);

        $this->assertDatabaseHas('aspirant_contests', [
            'id' => $contestId,
            'electoral_dataset_id' => $datasetId,
            'office' => 'president',
            'election_year' => 2027,
            'county_id' => null,
        ]);
    }

    public function test_creates_county_aspirant_contest(): void
    {
        $user = $this->createUser();
        $datasetId = $this->datasetId();
        $countyId = $this->countyId();

        $contestId = $this->service()->createContest(
            $user->id,
            'Test Governor Aspirant',
            2027,
            ElectoralOffice::GOVERNOR,
            countyId: $countyId,
            datasetId: $datasetId
        );

        $this->assertDatabaseHas('aspirant_contests', [
            'id' => $contestId,
            'office' => 'governor',
            'county_id' => $countyId,
        ]);
    }

    public function test_rejects_duplicate_contest(): void
    {
        $user = $this->createUser();
        $datasetId = $this->datasetId();

        $this->service()->createContest(
            $user->id,
            'First Registration',
            2027,
            ElectoralOffice::PRESIDENT,
            datasetId: $datasetId
        );

        $this->expectException(ValidationException::class);

        $this->service()->createContest(
            $user->id,
            'Duplicate Registration',
            2027,
            ElectoralOffice::PRESIDENT,
            datasetId: $datasetId
        );
    }

    public function test_rejects_national_contest_with_county(): void
    {
        $user = $this->createUser();
        $datasetId = $this->datasetId();
        $countyId = $this->countyId();

        $this->expectException(ValidationException::class);

        $this->service()->createContest(
            $user->id,
            'Invalid National Contest',
            2027,
            ElectoralOffice::PRESIDENT,
            countyId: $countyId,
            datasetId: $datasetId
        );
    }

    public function test_rejects_missing_dataset_without_creating_aspirant(): void
    {
        $user = $this->createUser();

        $this->expectException(ValidationException::class);

        try {
            $this->service()->createContest(
                $user->id,
                'Missing Dataset Aspirant',
                2027,
                ElectoralOffice::PRESIDENT,
                datasetId: 999999999
            );
        } finally {
            $this->assertDatabaseMissing('aspirants', [
                'user_id' => $user->id,
            ]);
        }
    }

    public function test_rejects_blank_display_name(): void
    {
        $user = $this->createUser();

        $this->expectException(ValidationException::class);

        $this->service()->createContest(
            $user->id,
            '   ',
            2027,
            ElectoralOffice::PRESIDENT
        );
    }
}
