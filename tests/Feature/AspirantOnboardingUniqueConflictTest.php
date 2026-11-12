<?php

namespace Tests\Feature;

use App\Enums\ElectoralOffice;
use App\Models\User;
use App\Services\AspirantOnboardingService;
use Illuminate\Database\UniqueConstraintViolationException;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use PDOException;
use Tests\TestCase;

class AspirantOnboardingUniqueConflictTest extends TestCase
{
    use DatabaseTransactions;

    private function datasetId(): int
    {
        return DB::table('electoral_datasets')->insertGetId([
            'name' => 'Unique Conflict Test Dataset',
            'version' => 'unique-conflict-' . uniqid(),
            'source_organization' => 'Test Fixture',
            'reference_year' => 2022,
            'status' => 'historical',
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    private function conflict(): UniqueConstraintViolationException
    {
        return new UniqueConstraintViolationException(
            'sqlite',
            'insert into aspirant_contests',
            [],
            new PDOException('Simulated unique constraint violation')
        );
    }

    public function test_matching_campaign_conflict_becomes_validation_error(): void
    {
        $user = User::factory()->create();
        $datasetId = $this->datasetId();

        $service = app(AspirantOnboardingService::class);

        $service->createContest(
            $user->id,
            'First Registration',
            2027,
            ElectoralOffice::PRESIDENT,
            datasetId: $datasetId
        );

        $aspirantId = DB::table('aspirants')
            ->where('user_id', $user->id)
            ->value('id');

        $this->assertNotNull($aspirantId);

        $this->assertDatabaseHas('aspirant_contests', [
            'aspirant_id' => $aspirantId,
            'office' => 'president',
            'election_year' => 2027,
        ]);

        // This establishes the matching campaign state. A separate
        // concurrency test is still needed to force the catch branch.
        try {
            $service->createContest(
                $user->id,
                'Second Registration',
                2027,
                ElectoralOffice::PRESIDENT,
                datasetId: $datasetId
            );

            $this->fail('Duplicate campaign was accepted.');
        } catch (ValidationException $exception) {
            $this->assertArrayHasKey(
                'contest',
                $exception->errors()
            );
        }
    }

    public function test_unique_exception_type_preserves_database_context(): void
    {
        $exception = $this->conflict();

        $this->assertInstanceOf(
            \Illuminate\Database\QueryException::class,
            $exception
        );

        $this->assertSame(
            'sqlite',
            $exception->getConnectionName()
        );

        $this->assertSame(
            'insert into aspirant_contests',
            $exception->getSql()
        );
    }

    public function test_insert_uniqueness_failure_is_rethrown_and_rolled_back(): void
    {
        $user = User::factory()->create();
        $datasetId = $this->datasetId();

        $connection = DB::connection();

        $injected = false;

        $connection->beforeExecuting(
            function ($sql, $bindings, $queryConnection) use (&$injected) {
                if (
                    !$injected
                    && preg_match(
                        '/^\s*insert\s+into\s+["`]?aspirant_contests["`]?\b/i',
                        $sql
                    )
                ) {
                    $injected = true;

                    throw new UniqueConstraintViolationException(
                        'sqlite',
                        $sql,
                        $bindings,
                        new PDOException(
                            'Simulated unrelated uniqueness failure'
                        )
                    );
                }
            }
        );

        try {
            $this->expectException(
                UniqueConstraintViolationException::class
            );

            try {
                app(AspirantOnboardingService::class)->createContest(
                    $user->id,
                    'Injected Conflict Aspirant',
                    2027,
                    ElectoralOffice::PRESIDENT,
                    datasetId: $datasetId
                );
            } finally {
                $this->assertTrue(
                    $injected,
                    'Expected insertion callback was not triggered.'
                );

                $this->assertDatabaseMissing('aspirants', [
                    'user_id' => $user->id,
                ]);
            }
        } finally {
            // The testing framework will disconnect the connection
            // during transaction teardown. This callback is scoped
            // to the current test application's connection.
        }
    }


    public function test_profile_creation_uniqueness_failure_is_rethrown(): void
    {
        $user = User::factory()->create();
        $datasetId = $this->datasetId();

        $injected = false;

        DB::connection()->beforeExecuting(
            function ($sql, $bindings, $connection) use (&$injected) {
                if (
                    !$injected
                    && preg_match(
                        '/^\s*insert\s+into\s+["`]?aspirants["`]?\b/i',
                        $sql
                    )
                ) {
                    $injected = true;

                    throw new UniqueConstraintViolationException(
                        'sqlite',
                        $sql,
                        $bindings,
                        new PDOException(
                            'Simulated aspirant user uniqueness conflict'
                        )
                    );
                }
            }
        );

        $caught = null;

        try {
            app(AspirantOnboardingService::class)->createContest(
                $user->id,
                'Profile Conflict Aspirant',
                2027,
                ElectoralOffice::PRESIDENT,
                datasetId: $datasetId
            );
        } catch (\Throwable $exception) {
            $caught = $exception;
        }

        $this->assertTrue(
            $injected,
            'Expected profile insertion callback was not triggered.'
        );

        $this->assertInstanceOf(
            UniqueConstraintViolationException::class,
            $caught
        );

        $this->assertDatabaseMissing('aspirants', [
            'user_id' => $user->id,
        ]);

        $this->assertSame(
            0,
            DB::table('aspirant_contests')
                ->join(
                    'aspirants',
                    'aspirants.id',
                    '=',
                    'aspirant_contests.aspirant_id'
                )
                ->where('aspirants.user_id', $user->id)
                ->count()
        );
    }

}
