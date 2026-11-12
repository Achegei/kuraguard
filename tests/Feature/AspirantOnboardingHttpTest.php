<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class AspirantOnboardingHttpTest extends TestCase
{
    use DatabaseTransactions;

    private function datasetId(): int
    {
        return DB::table('electoral_datasets')->insertGetId([
            'name' => 'HTTP Onboarding Test Dataset',
            'version' => 'http-onboarding-' . uniqid(),
            'source_organization' => 'Test Fixture',
            'reference_year' => 2022,
            'status' => 'historical',
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    public function test_guest_cannot_view_registration(): void
    {
        $this->get(route('aspirants.register'))
            ->assertRedirect(route('login'));
    }

    public function test_guest_cannot_submit_registration(): void
    {
        $this->post(route('aspirants.store'), [
            'display_name' => 'Guest Aspirant',
            'election_year' => 2027,
            'office' => 'president',
        ])->assertRedirect(route('login'));
    }

    public function test_authenticated_user_can_view_registration(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)
            ->get(route('aspirants.register'))
            ->assertOk()
            ->assertSee('Create your campaign')
            ->assertSee('Member of County Assembly');
    }

    public function test_invalid_office_is_rejected(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)
            ->from(route('aspirants.register'))
            ->post(route('aspirants.store'), [
                'display_name' => 'Test Aspirant',
                'election_year' => 2027,
                'office' => 'invalid_office',
            ])
            ->assertRedirect(route('aspirants.register'))
            ->assertSessionHasErrors('office');

        $this->assertDatabaseMissing('aspirants', [
            'user_id' => $user->id,
        ]);
    }

    public function test_authenticated_user_can_create_campaign(): void
    {
        $user = User::factory()->create();
        $datasetId = $this->datasetId();

        $response = $this->actingAs($user)
            ->post(route('aspirants.store'), [
                'display_name' => 'HTTP Test Presidential Aspirant',
                'election_year' => 2027,
                'office' => 'president',
            ]);

        $contest = DB::table('aspirant_contests')
            ->join(
                'aspirants',
                'aspirants.id',
                '=',
                'aspirant_contests.aspirant_id'
            )
            ->where('aspirants.user_id', $user->id)
            ->first();

        $this->assertNotNull($contest);

        $this->assertSame(
            $datasetId,
            (int) $contest->electoral_dataset_id
        );

        $response->assertRedirect(
            route('campaign.dashboard', [
                'contestId' => $contest->id,
            ])
        );

        $this->actingAs($user)
            ->get(route('campaign.dashboard', [
                'contestId' => $contest->id,
            ]))
            ->assertOk();
    }

    public function test_duplicate_registration_is_rejected(): void
    {
        $user = User::factory()->create();
        $this->datasetId();

        $payload = [
            'display_name' => 'Duplicate HTTP Aspirant',
            'election_year' => 2027,
            'office' => 'president',
        ];

        $this->actingAs($user)
            ->post(route('aspirants.store'), $payload)
            ->assertRedirect();

        $this->actingAs($user)
            ->from(route('aspirants.register'))
            ->post(route('aspirants.store'), $payload)
            ->assertRedirect(route('aspirants.register'))
            ->assertSessionHasErrors('contest');

        $aspirantId = DB::table('aspirants')
            ->where('user_id', $user->id)
            ->value('id');

        $this->assertSame(
            1,
            DB::table('aspirant_contests')
                ->where('aspirant_id', $aspirantId)
                ->count()
        );
    }
}
