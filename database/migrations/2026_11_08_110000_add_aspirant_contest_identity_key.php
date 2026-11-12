<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('aspirant_contests', function (Blueprint $table) {
            $table->string('contest_identity_key', 200)->nullable();
        });

        $contests = DB::table('aspirant_contests')
            ->orderBy('id')
            ->get();

        $seen = [];

        foreach ($contests as $contest) {
            $jurisdiction = match ($contest->office) {
                'president' => 'national',

                'governor', 'senator', 'woman_representative' =>
                    'county:' . $contest->county_id,

                'mp' => 'constituency:' . $contest->constituency_id,

                'mca' => 'ward:' . $contest->ward_id,

                default => throw new RuntimeException(
                    'Unknown electoral office in existing contest.'
                ),
            };

            $key = implode(':', [
                'aspirant',
                $contest->aspirant_id,
                $contest->office,
                $contest->election_year,
                $jurisdiction,
            ]);

            if (isset($seen[$key])) {
                throw new RuntimeException(
                    'Duplicate existing contest identity detected. '
                    . 'Resolve duplicates before applying this migration.'
                );
            }

            $seen[$key] = true;

            DB::table('aspirant_contests')
                ->where('id', $contest->id)
                ->update(['contest_identity_key' => $key]);
        }

        Schema::table('aspirant_contests', function (Blueprint $table) {
            $table->string('contest_identity_key', 200)
                ->nullable(false)
                ->change();
        });

        Schema::table('aspirant_contests', function (Blueprint $table) {
            $table->unique(
                'contest_identity_key',
                'aspirant_contest_identity_unique'
            );
        });
    }

    public function down(): void
    {
        Schema::table('aspirant_contests', function (Blueprint $table) {
            $table->dropUnique('aspirant_contest_identity_unique');
            $table->dropColumn('contest_identity_key');
        });
    }
};
