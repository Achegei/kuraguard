<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('public_role_applications', function (Blueprint $table) {
            $table->id();

            $table->foreignId('aspirant_contest_id')
                ->constrained('aspirant_contests')
                ->restrictOnDelete();

            $table->string('requested_role', 60);

            $table->string('full_name', 150);
            $table->string('email', 190);
            $table->string('phone', 30);

            $table->string('county', 120)->nullable();
            $table->string('constituency', 120)->nullable();
            $table->string('ward', 120)->nullable();

            $table->text('experience')->nullable();

            $table->string('status', 30)->default('pending');

            $table->foreignId('reviewed_by')
                ->nullable()
                ->constrained('users')
                ->nullOnDelete();

            $table->timestamp('reviewed_at')->nullable();
            $table->text('review_notes')->nullable();

            $table->foreignId('linked_user_id')
                ->nullable()
                ->constrained('users')
                ->nullOnDelete();

            $table->foreignId('linked_role_application_id')
                ->nullable()
                ->constrained('role_applications')
                ->nullOnDelete();

            $table->timestamps();

            $table->index([
                'aspirant_contest_id',
                'requested_role',
                'status'
            ], 'public_role_applications_campaign_role_status_idx');

            $table->index([
                'aspirant_contest_id',
                'email'
            ], 'public_role_applications_campaign_email_idx');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('public_role_applications');
    }
};
