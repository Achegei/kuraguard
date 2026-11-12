<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('public_role_applications', function (Blueprint $table) {
            $table->foreignId('electoral_dataset_id')
                ->nullable()
                ->constrained('electoral_datasets')
                ->restrictOnDelete();

            $table->string('geographic_level', 40)
                ->nullable();

            $table->foreignId('county_id')
                ->nullable()
                ->constrained('electoral_counties')
                ->restrictOnDelete();

            $table->foreignId('constituency_id')
                ->nullable()
                ->constrained('electoral_constituencies')
                ->restrictOnDelete();

            $table->foreignId('ward_id')
                ->nullable()
                ->constrained('electoral_wards')
                ->restrictOnDelete();

            $table->foreignId('polling_centre_id')
                ->nullable()
                ->constrained('electoral_polling_centres')
                ->restrictOnDelete();

            $table->foreignId('polling_station_id')
                ->nullable()
                ->constrained('electoral_polling_stations')
                ->restrictOnDelete();

            $table->index(
                ['aspirant_contest_id', 'county_id'],
                'public_applications_campaign_county_idx'
            );

            $table->index(
                ['aspirant_contest_id', 'constituency_id'],
                'public_applications_campaign_constituency_idx'
            );

            $table->index(
                ['aspirant_contest_id', 'ward_id'],
                'public_applications_campaign_ward_idx'
            );

            $table->index(
                ['aspirant_contest_id', 'polling_station_id'],
                'public_applications_campaign_station_idx'
            );
        });
    }

    public function down(): void
    {
        Schema::table('public_role_applications', function (Blueprint $table) {
            $table->dropIndex('public_applications_campaign_county_idx');
            $table->dropIndex('public_applications_campaign_constituency_idx');
            $table->dropIndex('public_applications_campaign_ward_idx');
            $table->dropIndex('public_applications_campaign_station_idx');

            $table->dropConstrainedForeignId('polling_station_id');
            $table->dropConstrainedForeignId('polling_centre_id');
            $table->dropConstrainedForeignId('ward_id');
            $table->dropConstrainedForeignId('constituency_id');
            $table->dropConstrainedForeignId('county_id');
            $table->dropColumn('geographic_level');
            $table->dropConstrainedForeignId('electoral_dataset_id');
        });
    }
};
