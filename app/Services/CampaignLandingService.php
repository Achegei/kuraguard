<?php

namespace App\Services;

use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

class CampaignLandingService
{
    public function authorizedContests(int $userId): Collection
    {
        $owned = DB::table('aspirant_contests as contests')
            ->join(
                'aspirants as aspirants',
                'aspirants.id',
                '=',
                'contests.aspirant_id'
            )
            ->where('aspirants.user_id', $userId)
            ->select('contests.id');

        $member = DB::table('aspirant_contests as contests')
            ->join(
                'aspirant_memberships as memberships',
                'memberships.aspirant_id',
                '=',
                'contests.aspirant_id'
            )
            ->where('memberships.user_id', $userId)
            ->where('memberships.status', 'active')
            ->select('contests.id');

        $contestIds = DB::query()
            ->fromSub($owned->union($member), 'accessible_contests')
            ->distinct()
            ->pluck('id');

        if ($contestIds->isEmpty()) {
            return collect();
        }

        return DB::table('aspirant_contests as contests')
            ->join(
                'aspirants as aspirants',
                'aspirants.id',
                '=',
                'contests.aspirant_id'
            )
            ->whereIn('contests.id', $contestIds)
            ->select([
                'contests.id',
                'contests.office',
                'contests.election_year',
                'contests.declaration_status',
                'aspirants.display_name',
            ])
            ->orderBy('contests.id')
            ->get();
    }
}
