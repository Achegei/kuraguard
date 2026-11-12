<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('aspirant_contests', function (Blueprint $table) {
            $table->boolean('public_recruitment_enabled')
                ->default(false);

            $table->boolean('public_recruitment_open')
                ->default(false);
        });
    }

    public function down(): void
    {
        Schema::table('aspirant_contests', function (Blueprint $table) {
            $table->dropColumn([
                'public_recruitment_enabled',
                'public_recruitment_open',
            ]);
        });
    }
};
