<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('elections', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('office');
            $table->date('election_date');
            $table->string('status')->default('draft');
            $table->boolean('is_demo')->default(false);
            $table->timestamps();
        });

        Schema::create('candidates', function (Blueprint $table) {
            $table->id();
            $table->foreignId('election_id')
                ->constrained()->cascadeOnDelete();
            $table->string('name');
            $table->string('party')->nullable();
            $table->boolean('is_client')->default(false);
            $table->timestamps();
        });

        Schema::create('polling_stations', function (Blueprint $table) {
            $table->id();
            $table->foreignId('election_id')
                ->constrained()->cascadeOnDelete();
            $table->string('county');
            $table->string('constituency');
            $table->string('ward');
            $table->string('name');
            $table->string('station_code');
            $table->unsignedInteger('registered_voters')->default(0);
            $table->string('status')->default('pending');
            $table->timestamps();

            $table->unique(['election_id', 'station_code']);
        });

        Schema::create('agents', function (Blueprint $table) {
            $table->id();
            $table->foreignId('election_id')
                ->constrained()->cascadeOnDelete();
            $table->foreignId('user_id')
                ->nullable()->constrained()->nullOnDelete();
            $table->string('name');
            $table->string('phone')->nullable();
            $table->string('status')->default('registered');
            $table->timestamps();
        });

        Schema::create('agent_assignments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('agent_id')
                ->constrained()->cascadeOnDelete();
            $table->foreignId('polling_station_id')
                ->constrained()->cascadeOnDelete();
            $table->string('status')->default('assigned');
            $table->timestamp('checked_in_at')->nullable();
            $table->timestamps();

            $table->unique(['agent_id', 'polling_station_id']);
        });

        Schema::create('result_submissions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('polling_station_id')
                ->constrained()->cascadeOnDelete();
            $table->foreignId('agent_id')
                ->nullable()->constrained()->nullOnDelete();
            $table->unsignedInteger('valid_votes')->default(0);
            $table->unsignedInteger('rejected_votes')->default(0);
            $table->string('status')->default('pending');
            $table->text('notes')->nullable();
            $table->timestamp('submitted_at')->nullable();
            $table->timestamp('verified_at')->nullable();
            $table->foreignId('verified_by')
                ->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
        });

        Schema::create('candidate_results', function (Blueprint $table) {
            $table->id();
            $table->foreignId('result_submission_id')
                ->constrained()->cascadeOnDelete();
            $table->foreignId('candidate_id')
                ->constrained()->cascadeOnDelete();
            $table->unsignedInteger('votes')->default(0);
            $table->timestamps();

            $table->unique([
                'result_submission_id',
                'candidate_id'
            ]);
        });

        Schema::create('result_evidence', function (Blueprint $table) {
            $table->id();
            $table->foreignId('result_submission_id')
                ->constrained()->cascadeOnDelete();
            $table->string('file_path');
            $table->string('file_hash', 64)->nullable();
            $table->string('form_type')->nullable();
            $table->foreignId('uploaded_by')
                ->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
        });

        Schema::create('incidents', function (Blueprint $table) {
            $table->id();
            $table->foreignId('polling_station_id')
                ->constrained()->cascadeOnDelete();
            $table->foreignId('agent_id')
                ->nullable()->constrained()->nullOnDelete();
            $table->string('category');
            $table->string('severity')->default('medium');
            $table->text('description');
            $table->string('status')->default('open');
            $table->timestamps();
        });

        Schema::create('audit_logs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')
                ->nullable()->constrained()->nullOnDelete();
            $table->string('action');
            $table->string('entity_type');
            $table->unsignedBigInteger('entity_id')->nullable();
            $table->json('metadata')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('audit_logs');
        Schema::dropIfExists('incidents');
        Schema::dropIfExists('result_evidence');
        Schema::dropIfExists('candidate_results');
        Schema::dropIfExists('result_submissions');
        Schema::dropIfExists('agent_assignments');
        Schema::dropIfExists('agents');
        Schema::dropIfExists('polling_stations');
        Schema::dropIfExists('candidates');
        Schema::dropIfExists('elections');
    }
};
