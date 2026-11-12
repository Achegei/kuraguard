<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Http\Middleware\ValidateCsrfToken;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class EnforcedAssignmentCsrfMiddleware extends ValidateCsrfToken
{
    protected function runningUnitTests()
    {
        return false;
    }
}

class FieldAssignmentCsrfTest extends TestCase
{
    use DatabaseTransactions;

    private User $owner;
    private int $contestId;
    private int $applicationId;

    protected function setUp(): void
    {
        parent::setUp();

        $this->app->singleton(
            ValidateCsrfToken::class,
            EnforcedAssignmentCsrfMiddleware::class
        );

        $ownerId = DB::table('users')->insertGetId([
            'name' => 'CSRF Assignment Owner',
            'email' => 'csrf-assignment-owner@example.test',
            'password' => bcrypt('test-password'),
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $this->owner = User::findOrFail($ownerId);

        $aspirantId = DB::table('aspirants')->insertGetId([
            'user_id' => $ownerId,
            'display_name' => 'CSRF Assignment Aspirant',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $this->contestId = DB::table('aspirant_contests')
            ->insertGetId([
            'contest_identity_key' => 'test-' . bin2hex(random_bytes(16)),
                'aspirant_id' => $aspirantId,
                'electoral_dataset_id' => 1,
                'election_year' => 2027,
                'office' => 'president',
                'created_at' => now(),
                'updated_at' => now(),
            ]);

        $applicantId = DB::table('users')->insertGetId([
            'name' => 'CSRF Assignment Applicant',
            'email' => 'csrf-assignment-applicant@example.test',
            'password' => bcrypt('test-password'),
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $this->applicationId = DB::table('role_applications')
            ->insertGetId([
                'aspirant_contest_id' => $this->contestId,
                'applicant_user_id' => $applicantId,
                'electoral_dataset_id' => 1,
                'requested_role' => 'national_coordinator',
                'geographic_level' => 'national',
                'status' => 'approved',
                'created_at' => now(),
                'updated_at' => now(),
            ]);
    }

    public function test_missing_csrf_token_is_rejected(): void
    {
        $this->actingAs($this->owner)
            ->postJson($this->endpoint())
            ->assertStatus(419);

        $this->assertNoAssignment();
    }

    public function test_invalid_csrf_token_is_rejected(): void
    {
        $this->actingAs($this->owner)
            ->withSession([
                '_token' => str_repeat('A', 40),
            ])
            ->postJson($this->endpoint(), [
                '_token' => str_repeat('B', 40),
            ])
            ->assertStatus(419);

        $this->assertNoAssignment();
    }

    public function test_valid_csrf_token_allows_assignment(): void
    {
        $token = str_repeat('C', 40);

        $this->actingAs($this->owner)
            ->withSession(['_token' => $token])
            ->postJson($this->endpoint(), [
                '_token' => $token,
            ])
            ->assertCreated()
            ->assertJsonPath('status', 'assigned')
            ->assertJsonPath(
                'application_id',
                $this->applicationId
            );

        $this->assertDatabaseHas('field_assignments', [
            'role_application_id' => $this->applicationId,
            'assigned_by' => $this->owner->id,
            'status' => 'assigned',
        ]);
    }

    private function endpoint(): string
    {
        return route('campaign.applications.assign', [
            'contestId' => $this->contestId,
            'applicationId' => $this->applicationId,
        ]);
    }

    private function assertNoAssignment(): void
    {
        $this->assertDatabaseMissing('field_assignments', [
            'role_application_id' => $this->applicationId,
        ]);
    }
}
