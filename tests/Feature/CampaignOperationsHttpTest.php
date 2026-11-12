<?php

namespace Tests\Feature;

use Tests\TestCase;

class CampaignOperationsHttpTest extends TestCase
{
    public function test_guest_cannot_view_campaign_operations(): void
    {
        $this->getJson('/campaigns/1/operations')
            ->assertUnauthorized();
    }

    public function test_guest_cannot_submit_station_report(): void
    {
        $this->postJson('/campaigns/1/operations/station-reports', [
            'polling_station_id' => 1,
            'attendance_status' => 'checked_in',
        ])->assertUnauthorized();
    }

    public function test_guest_cannot_report_incident(): void
    {
        $this->postJson('/campaigns/1/operations/incidents', [
            'polling_station_id' => 1,
            'category' => 'other',
            'severity' => 'low',
            'description' => 'Test incident',
        ])->assertUnauthorized();
    }

    public function test_guest_cannot_submit_results(): void
    {
        $this->postJson('/campaigns/1/results', [
            'polling_station_id' => 1,
            'valid_votes' => 100,
            'rejected_votes' => 2,
            'candidate_votes' => [
                'candidate_a' => 100,
            ],
        ])->assertUnauthorized();
    }

    public function test_guest_cannot_access_tally(): void
    {
        $this->getJson('/campaigns/1/results/tally')
            ->assertUnauthorized();
    }

    public function test_guest_cannot_review_results(): void
    {
        $this->postJson('/campaigns/1/results/1/review', [
            'decision' => 'verified',
        ])->assertUnauthorized();
    }

    public function test_guest_cannot_upload_result_evidence(): void
    {
        $this->postJson('/campaigns/1/results/1/evidence')
            ->assertUnauthorized();
    }
}
