<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('campaign_verified_result_slots', function (Blueprint $table) {
            $table->id();

            $table->foreignId('aspirant_contest_id')
                ->constrained('aspirant_contests')
                ->restrictOnDelete();

            $table->foreignId('electoral_polling_station_id')
                ->constrained('electoral_polling_stations')
                ->restrictOnDelete();

            $table->foreignId('campaign_result_submission_id')
                ->unique('campaign_verified_submission_unique')
                ->constrained('campaign_result_submissions')
                ->restrictOnDelete();

            $table->timestamps();

            $table->unique(
                [
                    'aspirant_contest_id',
                    'electoral_polling_station_id',
                ],
                'campaign_verified_station_unique'
            );
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('campaign_verified_result_slots');
    }
};
