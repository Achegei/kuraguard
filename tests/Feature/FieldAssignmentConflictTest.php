<?php

namespace Tests\Feature;

use App\Models\User;
use App\Services\FieldAssignmentService;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use Tests\TestCase;

class FieldAssignmentConflictTest extends TestCase
{
    use DatabaseTransactions;

    public function test_same_person_cannot_hold_duplicate_active_role_in_same_campaign(): void
    {
        $owner = $this->makeUser('owner');
        $applicant = $this->makeUser('applicant');

        $aspirantId = DB::table('aspirants')->insertGetId([
            'user_id' => $owner->id,
            'display_name' => 'Conflict Test Aspirant',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $contestId = DB::table('aspirant_contests')->insertGetId([
            'contest_identity_key' => 'test-' . bin2hex(random_bytes(16)),
            'aspirant_id' => $aspirantId,
            'electoral_dataset_id' => 1,
            'election_year' => 2027,
            'office' => 'president',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $firstApplicationId = $this->makeApplication(
            $contestId,
            $applicant->id
        );

        $secondApplicationId = $this->makeApplication(
            $contestId,
            $applicant->id
        );

        $service = app(FieldAssignmentService::class);

        $firstAssignmentId = $service->assign(
            $owner,
            $contestId,
            $firstApplicationId
        );

        $this->assertGreaterThan(0, $firstAssignmentId);

        try {
            $service->assign(
                $owner,
                $contestId,
                $secondApplicationId
            );

            $this->fail(
                'Expected duplicate active appointment to be rejected.'
            );
        } catch (ValidationException $exception) {
            $this->assertArrayHasKey(
                'applicant_user_id',
                $exception->errors()
            );
        }

        $this->assertSame(
            1,
            DB::table('field_assignments')
                ->where('status', 'assigned')
                ->whereNull('ended_at')
                ->count()
        );

        $this->assertDatabaseMissing('field_assignments', [
            'role_application_id' => $secondApplicationId,
        ]);
    }

    public function test_ended_assignment_allows_new_approved_application(): void
    {
        $owner = $this->makeUser('replacement-owner');
        $applicant = $this->makeUser('replacement-applicant');
        $contestId = $this->makeContest($owner);

        $firstApplication = $this->makeApplication(
            $contestId,
            $applicant->id
        );
        $secondApplication = $this->makeApplication(
            $contestId,
            $applicant->id
        );

        $service = app(FieldAssignmentService::class);

        $firstAssignment = $service->assign(
            $owner,
            $contestId,
            $firstApplication
        );

        $service->endAssignment(
            $owner,
            $contestId,
            $firstAssignment,
            'Replacement required'
        );

        $secondAssignment = $service->assign(
            $owner,
            $contestId,
            $secondApplication
        );

        $this->assertNotEquals(
            $firstAssignment,
            $secondAssignment
        );

        $this->assertDatabaseHas('field_assignments', [
            'id' => $firstAssignment,
            'status' => 'ended',
        ]);

        $this->assertDatabaseHas('field_assignments', [
            'id' => $secondAssignment,
            'status' => 'assigned',
        ]);
    }

    public function test_different_applicants_can_hold_same_role(): void
    {
        $owner = $this->makeUser('shared-role-owner');
        $firstApplicant = $this->makeUser('first-person');
        $secondApplicant = $this->makeUser('second-person');
        $contestId = $this->makeContest($owner);

        $service = app(FieldAssignmentService::class);

        $first = $service->assign(
            $owner,
            $contestId,
            $this->makeApplication(
                $contestId,
                $firstApplicant->id
            )
        );

        $second = $service->assign(
            $owner,
            $contestId,
            $this->makeApplication(
                $contestId,
                $secondApplicant->id
            )
        );

        $this->assertNotEquals($first, $second);
        $this->assertSame(
            2,
            DB::table('field_assignments')
                ->where('status', 'assigned')
                ->whereNull('ended_at')
                ->count()
        );
    }

    public function test_same_applicant_can_serve_different_campaigns(): void
    {
        $firstOwner = $this->makeUser('campaign-one-owner');
        $secondOwner = $this->makeUser('campaign-two-owner');
        $applicant = $this->makeUser('shared-applicant');

        $firstContest = $this->makeContest($firstOwner);
        $secondContest = $this->makeContest($secondOwner);

        $service = app(FieldAssignmentService::class);

        $first = $service->assign(
            $firstOwner,
            $firstContest,
            $this->makeApplication(
                $firstContest,
                $applicant->id
            )
        );

        $second = $service->assign(
            $secondOwner,
            $secondContest,
            $this->makeApplication(
                $secondContest,
                $applicant->id
            )
        );

        $this->assertNotEquals($first, $second);
    }

    public function test_rejected_duplicate_creates_no_extra_audit_record(): void
    {
        $owner = $this->makeUser('audit-owner');
        $applicant = $this->makeUser('audit-applicant');
        $contestId = $this->makeContest($owner);

        $service = app(FieldAssignmentService::class);

        $service->assign(
            $owner,
            $contestId,
            $this->makeApplication(
                $contestId,
                $applicant->id
            )
        );

        $auditCount = DB::table('audit_logs')
            ->where('action', 'field_assignment.created')
            ->count();

        try {
            $service->assign(
                $owner,
                $contestId,
                $this->makeApplication(
                    $contestId,
                    $applicant->id
                )
            );

            $this->fail('Expected duplicate rejection.');
        } catch (ValidationException $exception) {
            $this->assertArrayHasKey(
                'applicant_user_id',
                $exception->errors()
            );
        }

        $this->assertSame(
            $auditCount,
            DB::table('audit_logs')
                ->where('action', 'field_assignment.created')
                ->count()
        );
    }

    public function test_same_applicant_can_serve_different_counties(): void
    {
        $owner = $this->makeUser('two-counties-owner');
        $applicant = $this->makeUser('two-counties-applicant');
        $contestId = $this->makeContest($owner);

        $mombasa = DB::table('electoral_counties')
            ->where('code', '001')
            ->first();

        $kwale = DB::table('electoral_counties')
            ->where('code', '002')
            ->first();

        $this->assertNotNull($mombasa);
        $this->assertNotNull($kwale);

        $service = app(FieldAssignmentService::class);

        $first = $service->assign(
            $owner,
            $contestId,
            $this->makeCountyApplication(
                $contestId,
                $applicant->id,
                (int) $mombasa->id
            )
        );

        $second = $service->assign(
            $owner,
            $contestId,
            $this->makeCountyApplication(
                $contestId,
                $applicant->id,
                (int) $kwale->id
            )
        );

        $this->assertNotEquals($first, $second);

        $this->assertSame(
            2,
            DB::table('field_assignments')
                ->where('status', 'assigned')
                ->whereNull('ended_at')
                ->count()
        );
    }

    public function test_duplicate_active_county_appointment_is_rejected(): void
    {
        $owner = $this->makeUser('same-county-owner');
        $applicant = $this->makeUser('same-county-applicant');
        $contestId = $this->makeContest($owner);

        $mombasaId = DB::table('electoral_counties')
            ->where('code', '001')
            ->value('id');

        $this->assertNotNull($mombasaId);

        $firstApplication = $this->makeCountyApplication(
            $contestId,
            $applicant->id,
            (int) $mombasaId
        );

        $secondApplication = $this->makeCountyApplication(
            $contestId,
            $applicant->id,
            (int) $mombasaId
        );

        $service = app(FieldAssignmentService::class);

        $service->assign(
            $owner,
            $contestId,
            $firstApplication
        );

        try {
            $service->assign(
                $owner,
                $contestId,
                $secondApplication
            );

            $this->fail(
                'Expected duplicate county appointment rejection.'
            );
        } catch (ValidationException $exception) {
            $this->assertArrayHasKey(
                'applicant_user_id',
                $exception->errors()
            );
        }

        $this->assertDatabaseMissing('field_assignments', [
            'role_application_id' => $secondApplication,
        ]);
    }

    private function makeCountyApplication(
        int $contestId,
        int $applicantId,
        int $countyId
    ): int {
        return DB::table('role_applications')->insertGetId([
            'aspirant_contest_id' => $contestId,
            'applicant_user_id' => $applicantId,
            'electoral_dataset_id' => 1,
            'requested_role' => 'county_coordinator',
            'geographic_level' => 'county',
            'county_id' => $countyId,
            'status' => 'approved',
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    private function makeContest(User $owner): int
    {
        $aspirantId = DB::table('aspirants')->insertGetId([
            'user_id' => $owner->id,
            'display_name' => 'Conflict Test Campaign',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

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

    private function makeUser(string $suffix): User
    {
        $id = DB::table('users')->insertGetId([
            'name' => 'Conflict Test '.$suffix,
            'email' => 'conflict-'.$suffix.'-'
                . uniqid().'@example.test',
            'password' => bcrypt('test-password'),
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        return User::findOrFail($id);
    }

    private function makeApplication(
        int $contestId,
        int $applicantId
    ): int {
        return DB::table('role_applications')->insertGetId([
            'aspirant_contest_id' => $contestId,
            'applicant_user_id' => $applicantId,
            'electoral_dataset_id' => 1,
            'requested_role' => 'national_coordinator',
            'geographic_level' => 'national',
            'status' => 'approved',
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }
}
