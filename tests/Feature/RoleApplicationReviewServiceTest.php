<?php

namespace Tests\Feature;

use App\Models\User;
use App\Services\RoleApplicationReviewService;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use Symfony\Component\HttpKernel\Exception\HttpException;
use Tests\TestCase;

class RoleApplicationReviewServiceTest extends TestCase
{
    use DatabaseTransactions;

    private RoleApplicationReviewService $service;
    private User $owner;
    private User $reviewer;
    private User $outsider;
    private User $applicant;
    private int $contestId;
    private int $otherContestId;
    private int $applicationId;

    protected function setUp(): void
    {
        parent::setUp();

        $this->service = app(RoleApplicationReviewService::class);

        $this->owner = $this->createUser('owner');
        $this->reviewer = $this->createUser('reviewer');
        $this->outsider = $this->createUser('outsider');
        $this->applicant = $this->createUser('applicant');
        $otherOwner = $this->createUser('other-owner');

        $aspirantId = $this->createAspirant($this->owner);
        $otherAspirantId = $this->createAspirant($otherOwner);

        $this->contestId = $this->createContest($aspirantId);
        $this->otherContestId = $this->createContest($otherAspirantId);

        DB::table('aspirant_memberships')->insert([
            'aspirant_id' => $aspirantId,
            'user_id' => $this->reviewer->id,
            'role' => 'campaign_reviewer',
            'status' => 'active',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $this->applicationId = $this->createRoleApplicationFixture(
            $this->contestId
        );
    }

    public function test_owner_approves_pending_application(): void
    {
        $this->service->approve(
            $this->owner,
            $this->contestId,
            $this->applicationId,
            'Verified application.'
        );

        $application = $this->application();

        $this->assertSame('approved', $application->status);
        $this->assertSame(
            $this->owner->id,
            (int) $application->reviewed_by
        );
        $this->assertNotNull($application->reviewed_at);
        $this->assertSame(
            'Verified application.',
            $application->review_notes
        );
    }

    public function test_authorized_reviewer_rejects_application(): void
    {
        $this->service->reject(
            $this->reviewer,
            $this->contestId,
            $this->applicationId,
            'Insufficient information.'
        );

        $application = $this->application();

        $this->assertSame('rejected', $application->status);
        $this->assertSame(
            $this->reviewer->id,
            (int) $application->reviewed_by
        );
        $this->assertNotNull($application->reviewed_at);
    }

    public function test_outsider_cannot_approve(): void
    {
        try {
            $this->service->approve(
                $this->outsider,
                $this->contestId,
                $this->applicationId
            );

            $this->fail('Expected HTTP 403.');
        } catch (HttpException $exception) {
            $this->assertSame(403, $exception->getStatusCode());
        }

        $this->assertSame('pending', $this->application()->status);
    }

    public function test_reviewer_cannot_review_another_campaign(): void
    {
        $otherApplication = $this->createRoleApplicationFixture(
            $this->otherContestId
        );

        try {
            $this->service->approve(
                $this->reviewer,
                $this->otherContestId,
                $otherApplication
            );

            $this->fail('Expected HTTP 403.');
        } catch (HttpException $exception) {
            $this->assertSame(403, $exception->getStatusCode());
        }

        $this->assertSame(
            'pending',
            DB::table('role_applications')
                ->where('id', $otherApplication)
                ->value('status')
        );
    }

    public function test_application_from_another_contest_is_not_found(): void
    {
        try {
            $this->service->approve(
                $this->owner,
                $this->contestId,
                $this->createRoleApplicationFixture($this->otherContestId)
            );

            $this->fail('Expected HTTP 404.');
        } catch (HttpException $exception) {
            $this->assertSame(404, $exception->getStatusCode());
        }
    }

    public function test_approved_application_cannot_be_reviewed_again(): void
    {
        $this->service->approve(
            $this->owner,
            $this->contestId,
            $this->applicationId
        );

        $this->expectException(ValidationException::class);

        $this->service->reject(
            $this->reviewer,
            $this->contestId,
            $this->applicationId
        );
    }

    public function test_review_notes_cannot_exceed_limit(): void
    {
        try {
            $this->service->reject(
                $this->owner,
                $this->contestId,
                $this->applicationId,
                str_repeat('X', 5001)
            );

            $this->fail('Expected validation exception.');
        } catch (ValidationException $exception) {
            $this->assertArrayHasKey(
                'review_notes',
                $exception->errors()
            );
        }

        $this->assertSame('pending', $this->application()->status);
    }

    public function test_failed_second_review_preserves_first_decision(): void
    {
        $this->service->reject(
            $this->owner,
            $this->contestId,
            $this->applicationId,
            'Original decision.'
        );

        try {
            $this->service->approve(
                $this->reviewer,
                $this->contestId,
                $this->applicationId
            );

            $this->fail('Expected validation exception.');
        } catch (ValidationException $exception) {
            $this->assertArrayHasKey('status', $exception->errors());
        }

        $application = $this->application();

        $this->assertSame('rejected', $application->status);
        $this->assertSame(
            $this->owner->id,
            (int) $application->reviewed_by
        );
        $this->assertSame(
            'Original decision.',
            $application->review_notes
        );
    }

    private function createUser(string $suffix): User
    {
        $id = DB::table('users')->insertGetId([
            'name' => 'Review Test '.$suffix,
            'email' => 'review-test-'.$suffix.'@example.test',
            'password' => bcrypt('test-password'),
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        return User::findOrFail($id);
    }

    private function createAspirant(User $user): int
    {
        return DB::table('aspirants')->insertGetId([
            'user_id' => $user->id,
            'display_name' => 'Review Test Aspirant',
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    private function createContest(int $aspirantId): int
    {
        return DB::table('aspirant_contests')->insertGetId([
            'contest_identity_key' => 'test-' . bin2hex(random_bytes(16)),
            'aspirant_id' => $aspirantId,
            'electoral_dataset_id' => 1,
            'election_year' => 2027,
            'office' => 'president',
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    private function createRoleApplicationFixture(int $contestId): int
    {
        return DB::table('role_applications')->insertGetId([
            'aspirant_contest_id' => $contestId,
            'applicant_user_id' => $this->applicant->id,
            'electoral_dataset_id' => 1,
            'requested_role' => 'national_coordinator',
            'geographic_level' => 'national',
            'status' => 'pending',
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    private function application(): object
    {
        return DB::table('role_applications')
            ->where('id', $this->applicationId)
            ->first();
    }
}
