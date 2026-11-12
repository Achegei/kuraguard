<?php

namespace App\Providers;

use App\Models\User;
use App\Policies\AspirantContestPolicy;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        Gate::define(
            'campaign.view',
            fn (User $user, int $contestId): bool =>
                app(AspirantContestPolicy::class)
                    ->view($user, $contestId)
        );

        Gate::define(
            'campaign.review-applications',
            fn (User $user, int $contestId): bool =>
                app(AspirantContestPolicy::class)
                    ->reviewApplications($user, $contestId)
        );

        Gate::define(
            'campaign.manage-incidents',
            fn (User $user, int $contestId): bool =>
                app(AspirantContestPolicy::class)
                    ->manageIncidents($user, $contestId)
        );

        Gate::define(
            'campaign.assign-agents',
            fn (User $user, int $contestId): bool =>
                app(AspirantContestPolicy::class)
                    ->assignAgents($user, $contestId)
        );
    }
}
