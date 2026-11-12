<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('aspirants', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')
                ->constrained('users')->restrictOnDelete();
            $table->string('display_name', 150);
            $table->string('verification_status')
                ->default('self_declared');
            $table->timestamps();
            $table->unique('user_id');
        });

        Schema::create('aspirant_contests', function (Blueprint $table) {
            $table->id();
            $table->foreignId('aspirant_id')
                ->constrained('aspirants')->restrictOnDelete();
            $table->foreignId('electoral_dataset_id')
                ->constrained('electoral_datasets')->restrictOnDelete();
            $table->unsignedSmallInteger('election_year');
            $table->string('office', 40);
            $table->foreignId('county_id')->nullable()
                ->constrained('electoral_counties')->restrictOnDelete();
            $table->foreignId('constituency_id')->nullable()
                ->constrained('electoral_constituencies')->restrictOnDelete();
            $table->foreignId('ward_id')->nullable()
                ->constrained('electoral_wards')->restrictOnDelete();
            $table->string('declaration_status')
                ->default('self_declared');
            $table->timestamps();
            $table->index(['office', 'election_year']);
        });

        Schema::create('aspirant_memberships', function (Blueprint $table) {
            $table->id();
            $table->foreignId('aspirant_id')
                ->constrained('aspirants')->restrictOnDelete();
            $table->foreignId('user_id')
                ->constrained('users')->restrictOnDelete();
            $table->string('role', 50);
            $table->string('status', 30)->default('active');
            $table->timestamps();
            $table->unique(['aspirant_id', 'user_id', 'role']);
        });

        Schema::create('user_role_assignments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')
                ->constrained('users')->restrictOnDelete();
            $table->string('role', 50);
            $table->timestamps();
            $table->unique(['user_id', 'role']);
        });

        Schema::create('role_applications', function (Blueprint $table) {
            $table->id();
            $table->foreignId('aspirant_contest_id')
                ->constrained('aspirant_contests')->restrictOnDelete();
            $table->foreignId('applicant_user_id')
                ->constrained('users')->restrictOnDelete();
            $table->foreignId('electoral_dataset_id')
                ->constrained('electoral_datasets')->restrictOnDelete();
            $table->string('requested_role', 40);
            $table->string('geographic_level', 40);
            $table->foreignId('county_id')->nullable()
                ->constrained('electoral_counties')->restrictOnDelete();
            $table->foreignId('constituency_id')->nullable()
                ->constrained('electoral_constituencies')->restrictOnDelete();
            $table->foreignId('ward_id')->nullable()
                ->constrained('electoral_wards')->restrictOnDelete();
            $table->foreignId('polling_centre_id')->nullable()
                ->constrained('electoral_polling_centres')->restrictOnDelete();
            $table->foreignId('polling_station_id')->nullable()
                ->constrained('electoral_polling_stations')->restrictOnDelete();
            $table->string('status', 30)->default('pending');
            $table->foreignId('reviewed_by')->nullable()
                ->constrained('users')->nullOnDelete();
            $table->timestamp('reviewed_at')->nullable();
            $table->text('review_notes')->nullable();
            $table->timestamps();
            $table->index([
                'aspirant_contest_id',
                'status',
                'requested_role'
            ], 'role_applications_contest_status_role_idx');
        });

        Schema::create('field_assignments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('role_application_id')
                ->constrained('role_applications')->restrictOnDelete();
            $table->foreignId('assigned_by')
                ->constrained('users')->restrictOnDelete();
            $table->string('status', 30)->default('assigned');
            $table->timestamp('assigned_at');
            $table->timestamp('ended_at')->nullable();
            $table->timestamps();
            $table->unique('role_application_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('field_assignments');
        Schema::dropIfExists('role_applications');
        Schema::dropIfExists('user_role_assignments');
        Schema::dropIfExists('aspirant_memberships');
        Schema::dropIfExists('aspirant_contests');
        Schema::dropIfExists('aspirants');
    }
};
