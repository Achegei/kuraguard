<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('electoral_datasets', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('version')->unique();
            $table->string('source_organization');
            $table->string('source_url')->nullable();
            $table->string('source_hash', 64)->nullable();
            $table->unsignedSmallInteger('reference_year');
            $table->date('published_at')->nullable();
            $table->string('status')->default('draft');
            $table->text('notes')->nullable();
            $table->timestamps();
        });

        Schema::create('electoral_counties', function (Blueprint $table) {
            $table->id();
            $table->string('code')->unique();
            $table->string('name');
            $table->timestamps();
        });

        Schema::create('electoral_constituencies', function (Blueprint $table) {
            $table->id();
            $table->foreignId('county_id')
                ->constrained('electoral_counties')
                ->restrictOnDelete();
            $table->string('code')->unique();
            $table->string('name');
            $table->timestamps();

            $table->index(['county_id', 'name']);
        });

        Schema::create('electoral_wards', function (Blueprint $table) {
            $table->id();
            $table->foreignId('constituency_id')
                ->constrained('electoral_constituencies')
                ->restrictOnDelete();
            $table->string('code')->unique();
            $table->string('name');
            $table->timestamps();

            $table->index(['constituency_id', 'name']);
        });

        Schema::create('electoral_polling_centres', function (Blueprint $table) {
            $table->id();
            $table->foreignId('ward_id')
                ->constrained('electoral_wards')
                ->restrictOnDelete();
            $table->string('code')->unique();
            $table->string('name');
            $table->timestamps();

            $table->index(['ward_id', 'name']);
        });

        Schema::create('electoral_polling_stations', function (Blueprint $table) {
            $table->id();
            $table->foreignId('polling_centre_id')
                ->constrained('electoral_polling_centres')
                ->restrictOnDelete();
            $table->string('code')->unique();
            $table->string('name');
            $table->string('stream_number')->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();

            $table->index(['polling_centre_id', 'name']);
        });

        Schema::create('electoral_register_snapshots', function (Blueprint $table) {
            $table->id();
            $table->foreignId('dataset_id')
                ->constrained('electoral_datasets')
                ->restrictOnDelete();
            $table->foreignId('polling_station_id')
                ->constrained('electoral_polling_stations')
                ->restrictOnDelete();
            $table->unsignedInteger('registered_voters');
            $table->timestamps();

            $table->unique(
                ['dataset_id', 'polling_station_id'],
                'electoral_register_dataset_station_unique'
            );
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('electoral_register_snapshots');
        Schema::dropIfExists('electoral_polling_stations');
        Schema::dropIfExists('electoral_polling_centres');
        Schema::dropIfExists('electoral_wards');
        Schema::dropIfExists('electoral_constituencies');
        Schema::dropIfExists('electoral_counties');
        Schema::dropIfExists('electoral_datasets');
    }
};
