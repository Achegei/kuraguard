<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('campaign_station_reports', function (Blueprint $table) {
            $table->id();
            $table->foreignId('aspirant_contest_id')
                ->constrained('aspirant_contests')->restrictOnDelete();
            $table->foreignId('electoral_polling_station_id')
                ->constrained('electoral_polling_stations')->restrictOnDelete();
            $table->foreignId('field_assignment_id')
                ->constrained('field_assignments')->restrictOnDelete();
            $table->string('attendance_status', 30)->default('pending');
            $table->timestamp('checked_in_at')->nullable();
            $table->unsignedInteger('reported_turnout')->nullable();
            $table->text('notes')->nullable();
            $table->timestamps();

            $table->unique([
                'aspirant_contest_id',
                'electoral_polling_station_id',
                'field_assignment_id'
            ], 'campaign_station_report_unique');
        });

        Schema::create('campaign_incidents', function (Blueprint $table) {
            $table->id();
            $table->foreignId('aspirant_contest_id')
                ->constrained('aspirant_contests')->restrictOnDelete();
            $table->foreignId('electoral_polling_station_id')
                ->constrained('electoral_polling_stations')->restrictOnDelete();
            $table->foreignId('reported_by')
                ->constrained('users')->restrictOnDelete();
            $table->string('category', 60);
            $table->string('severity', 20)->default('medium');
            $table->text('description');
            $table->string('status', 30)->default('open');
            $table->timestamp('resolved_at')->nullable();
            $table->timestamps();

            $table->index([
                'aspirant_contest_id',
                'status',
                'severity'
            ], 'campaign_incidents_status_index');
        });

        Schema::create('campaign_result_submissions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('aspirant_contest_id')
                ->constrained('aspirant_contests')->restrictOnDelete();
            $table->foreignId('electoral_polling_station_id')
                ->constrained('electoral_polling_stations')->restrictOnDelete();
            $table->foreignId('submitted_by')
                ->constrained('users')->restrictOnDelete();
            $table->unsignedInteger('valid_votes');
            $table->unsignedInteger('rejected_votes')->default(0);
            $table->json('candidate_votes');
            $table->string('status', 30)->default('pending');
            $table->text('notes')->nullable();
            $table->timestamp('submitted_at')->nullable();
            $table->timestamp('verified_at')->nullable();
            $table->foreignId('verified_by')
                ->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();

            $table->index([
                'aspirant_contest_id',
                'electoral_polling_station_id',
                'status'
            ], 'campaign_results_station_status_index');
        });

        Schema::create('campaign_result_evidence', function (Blueprint $table) {
            $table->id();
            $table->foreignId('campaign_result_submission_id')
                ->constrained('campaign_result_submissions')->restrictOnDelete();
            $table->foreignId('uploaded_by')
                ->constrained('users')->restrictOnDelete();
            $table->string('file_path');
            $table->string('file_hash', 64);
            $table->string('form_type', 40)->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('campaign_result_evidence');
        Schema::dropIfExists('campaign_result_submissions');
        Schema::dropIfExists('campaign_incidents');
        Schema::dropIfExists('campaign_station_reports');
    }
};
