<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('agent_activation_invitations', function (Blueprint $table) {
            $table->id();

            $table->foreignId('public_role_application_id')
                ->constrained('public_role_applications');

            $table->foreignId('aspirant_contest_id')
                ->constrained('aspirant_contests');

            $table->string('email', 190);

            $table->string('token_hash', 64)->unique();

            $table->foreignId('invited_by')
                ->constrained('users');

            $table->timestamp('expires_at');
            $table->timestamp('used_at')->nullable();
            $table->timestamp('revoked_at')->nullable();

            $table->timestamps();

            $table->index([
                'aspirant_contest_id',
                'public_role_application_id'
            ], 'agent_invitation_campaign_application_idx');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('agent_activation_invitations');
    }
};
