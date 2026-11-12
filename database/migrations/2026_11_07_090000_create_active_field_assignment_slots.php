<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create(
            'active_field_assignment_slots',
            function (Blueprint $table): void {
                $table->id();

                $table->foreignId('field_assignment_id')
                    ->constrained('field_assignments')
                    ->restrictOnDelete();

                $table->foreignId('aspirant_contest_id')
                    ->constrained('aspirant_contests')
                    ->restrictOnDelete();

                $table->foreignId('applicant_user_id')
                    ->constrained('users')
                    ->restrictOnDelete();

                $table->foreignId('electoral_dataset_id')
                    ->constrained('electoral_datasets')
                    ->restrictOnDelete();

                $table->string('requested_role', 40);
                $table->string('geographic_level', 40);

                $table->unsignedBigInteger('geographic_id');

                $table->timestamps();

                $table->unique(
                    'field_assignment_id',
                    'active_assignment_slot_assignment_unique'
                );

                $table->unique(
                    [
                        'aspirant_contest_id',
                        'applicant_user_id',
                        'electoral_dataset_id',
                        'requested_role',
                        'geographic_level',
                        'geographic_id',
                    ],
                    'active_assignment_slot_identity_unique'
                );
            }
        );
    }

    public function down(): void
    {
        Schema::dropIfExists('active_field_assignment_slots');
    }
};
