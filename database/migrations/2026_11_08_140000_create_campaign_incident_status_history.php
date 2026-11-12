<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create(
            'campaign_incident_status_history',
            function (Blueprint $table): void {
                $table->id();

                $table->foreignId('campaign_incident_id')
                    ->constrained('campaign_incidents')
                    ->restrictOnDelete();

                $table->foreignId('aspirant_contest_id')
                    ->constrained('aspirant_contests')
                    ->restrictOnDelete();

                $table->foreignId('changed_by')
                    ->constrained('users')
                    ->restrictOnDelete();

                $table->string('from_status', 30);
                $table->string('to_status', 30);
                $table->text('notes')->nullable();

                $table->timestamp('created_at')->useCurrent();

                $table->index(
                    ['campaign_incident_id', 'created_at', 'id'],
                    'campaign_incident_history_timeline_index'
                );

                $table->index(
                    ['aspirant_contest_id', 'created_at'],
                    'campaign_incident_history_contest_index'
                );
            }
        );
    }

    public function down(): void
    {
        Schema::dropIfExists(
            'campaign_incident_status_history'
        );
    }
};
