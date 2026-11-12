<?php

namespace Tests\Feature;

use App\Enums\ElectoralOffice;
use App\Models\User;
use App\Services\AspirantOnboardingService;
use Illuminate\Database\QueryException;
use Illuminate\Support\Facades\DB;
use PDOException;
use Tests\TestCase;

class AspirantOnboardingRetryExhaustionTest extends TestCase
{
    public function test_persistent_sqlite_lock_stops_after_three_attempts(): void
    {
        $this->assertSame(
            'sqlite',
            DB::connection()->getDriverName()
        );

        $this->assertSame(
            0,
            DB::connection()->transactionLevel()
        );

        $user = User::factory()->create();

        $datasetId = DB::table('electoral_datasets')->insertGetId([
            'name' => 'Retry Exhaustion Dataset',
            'version' => 'retry-exhaustion-' . uniqid(),
            'source_organization' => 'Test Fixture',
            'reference_year' => 2022,
            'status' => 'historical',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $attempts = 0;

        DB::connection()->beforeExecuting(
            function ($sql, $bindings, $connection) use (&$attempts) {
                if (! preg_match(
                    '/^\s*insert\s+into\s+["`]?aspirant_contests["`]?\b/i',
                    $sql
                )) {
                    return;
                }

                $attempts++;

                throw new QueryException(
                    'sqlite',
                    $sql,
                    $bindings,
                    new PDOException('database is locked', 5)
                );
            }
        );

        $caught = null;

        try {
            app(AspirantOnboardingService::class)->createContest(
                $user->id,
                'Retry Exhaustion Aspirant',
                2027,
                ElectoralOffice::PRESIDENT,
                datasetId: $datasetId
            );
        } catch (\Throwable $exception) {
            $caught = $exception;
        }

        $this->assertInstanceOf(QueryException::class, $caught);

        $this->assertStringContainsString(
            'database is locked',
            $caught->getMessage()
        );

        $this->assertSame(3, $attempts);

        $this->assertSame(
            0,
            DB::table('aspirants')
                ->where('user_id', $user->id)
                ->count()
        );

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

        $this->assertSame(
            0,
            DB::connection()->transactionLevel()
        );
    }
}
