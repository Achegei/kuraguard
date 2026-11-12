<?php

namespace App\Services;

use Illuminate\Support\Facades\DB;

class AspirantAuthorizationService
{
    private const MANAGER_ROLES = [
        'campaign_admin',
    ];

    private const REVIEWER_ROLES = [
        'campaign_admin',
        'campaign_reviewer',
    ];

    public function canViewCampaign(
        int $userId,
        int $contestId
    ): bool {
        if ($this->isOwner($userId, $contestId)) {
            return true;
        }

        return $this->hasActiveMembership(
            $userId,
            $contestId
        );
    }

    public function canReviewApplications(
        int $userId,
        int $contestId
    ): bool {
        if ($this->isOwner($userId, $contestId)) {
            return true;
        }

        return $this->hasActiveMembership(
            $userId,
            $contestId,
            self::REVIEWER_ROLES
        );
    }

    public function canAssignAgents(
        int $userId,
        int $contestId
    ): bool {
        if ($this->isOwner($userId, $contestId)) {
            return true;
        }

        return $this->hasActiveMembership(
            $userId,
            $contestId,
            self::MANAGER_ROLES
        );
    }

    public function canManageIncidents(
        int $userId,
        int $contestId
    ): bool {
        if ($this->isOwner($userId, $contestId)) {
            return true;
        }

        return $this->hasActiveMembership(
            $userId,
            $contestId,
            self::MANAGER_ROLES
        );
    }

    private function isOwner(
        int $userId,
        int $contestId
    ): bool {
        return DB::table('aspirant_contests as contests')
            ->join(
                'aspirants as aspirants',
                'aspirants.id',
                '=',
                'contests.aspirant_id'
            )
            ->where('contests.id', $contestId)
            ->where('aspirants.user_id', $userId)
            ->exists();
    }

    private function hasActiveMembership(
        int $userId,
        int $contestId,
        ?array $allowedRoles = null
    ): bool {
        $query = DB::table(
            'aspirant_contests as contests'
        )
            ->join(
                'aspirant_memberships as memberships',
                'memberships.aspirant_id',
                '=',
                'contests.aspirant_id'
            )
            ->where('contests.id', $contestId)
            ->where('memberships.user_id', $userId)
            ->where('memberships.status', 'active');

        if ($allowedRoles !== null) {
            $query->whereIn(
                'memberships.role',
                $allowedRoles
            );
        }

        return $query->exists();
    }
}
