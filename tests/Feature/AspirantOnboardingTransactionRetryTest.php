<?php

namespace Tests\Feature;

use App\Enums\ElectoralOffice;
use App\Models\User;
use App\Services\AspirantOnboardingService;
use Illuminate\Database\QueryException;
use Illuminate\Support\Facades\DB;
use PDOException;
use Tests\TestCase;

class AspirantOnboardingTransactionRetryTest extends TestCase
{
    public function test_sqlite_lock_error_retries_registration_without_duplicates(): void
    {
        $this->assertSame(
            'sqlite',
            DB::connection()->getDriverName()
        );

        $this->assertSame(
            0,
            DB::connection()->transactionLevel(),
            'This test requires an outermost transaction.'
        );

        $user = User::factory()->create();

        $datasetId = DB::table('electoral_datasets')->insertGetId([
            'name' => 'Transaction Retry Test Dataset',
            'version' => 'retry-test-' . uniqid(),
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

                if ($attempts === 1) {
                    throw new QueryException(
                        'sqlite',
                        $sql,
                        $bindings,
                        new PDOException('database is locked', 5)
                    );
                }
            }
        );

        $contestId = app(AspirantOnboardingService::class)
            ->createContest(
                $user->id,
                'Retry Test Aspirant',
                2027,
                ElectoralOffice::PRESIDENT,
                datasetId: $datasetId
            );

        $this->assertSame(2, $attempts);

        $this->assertSame(
            1,
            DB::table('aspirants')
                ->where('user_id', $user->id)
                ->count()
        );

        $this->assertSame(
            1,
            DB::table('aspirant_contests')
                ->where('id', $contestId)
                ->count()
        );

        $this->assertSame(
            0,
            DB::connection()->transactionLevel()
        );
    }
}
