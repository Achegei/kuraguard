<?php

namespace App\Policies;

use App\Models\User;
use App\Services\AspirantAuthorizationService;

class AspirantContestPolicy
{
    public function __construct(
        private readonly AspirantAuthorizationService $authorization
    ) {
    }

    public function view(
        User $user,
        int $contestId
    ): bool {
        return $this->authorization->canViewCampaign(
            (int) $user->id,
            $contestId
        );
    }

    public function reviewApplications(
        User $user,
        int $contestId
    ): bool {
        return $this->authorization->canReviewApplications(
            (int) $user->id,
            $contestId
        );
    }

    public function manageIncidents(
        User $user,
        int $contestId
    ): bool {
        return $this->authorization->canManageIncidents(
            (int) $user->id,
            $contestId
        );
    }

    public function assignAgents(
        User $user,
        int $contestId
    ): bool {
        return $this->authorization->canAssignAgents(
            (int) $user->id,
            $contestId
        );
    }
}
