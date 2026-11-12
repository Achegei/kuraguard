<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('electoral_polling_centres', function (Blueprint $table) {
            $table->dropUnique(
                'electoral_polling_centres_code_unique'
            );

            $table->unique(
                ['ward_id', 'code'],
                'electoral_polling_centres_ward_code_unique'
            );
        });
    }

    public function down(): void
    {
        $duplicateCodes = DB::table('electoral_polling_centres')
            ->select('code')
            ->groupBy('code')
            ->havingRaw('COUNT(*) > 1')
            ->exists();

        if ($duplicateCodes) {
            throw new \RuntimeException(
                'Cannot roll back: polling-centre codes are '
                . 'reused across wards.'
            );
        }

        Schema::table('electoral_polling_centres', function (Blueprint $table) {
            $table->dropUnique(
                'electoral_polling_centres_ward_code_unique'
            );

            $table->unique(
                'code',
                'electoral_polling_centres_code_unique'
            );
        });
    }
};
