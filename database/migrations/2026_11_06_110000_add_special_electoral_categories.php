<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('electoral_special_categories', function (Blueprint $table) {
            $table->id();
            $table->string('code')->unique();
            $table->string('name');
            $table->timestamps();
        });

        Schema::table('electoral_constituencies', function (Blueprint $table) {
            $table->foreignId('county_id')
                ->nullable()
                ->change();

            $table->foreignId('special_category_id')
                ->nullable()
                ->constrained('electoral_special_categories')
                ->restrictOnDelete();
        });

        DB::statement(
            'CREATE TRIGGER electoral_constituencies_parent_insert
             BEFORE INSERT ON electoral_constituencies
             FOR EACH ROW
             WHEN (
                 (NEW.county_id IS NULL AND NEW.special_category_id IS NULL)
                 OR
                 (NEW.county_id IS NOT NULL AND NEW.special_category_id IS NOT NULL)
             )
             BEGIN
                 SELECT RAISE(ABORT, "Exactly one electoral parent is required");
             END'
        );

        DB::statement(
            'CREATE TRIGGER electoral_constituencies_parent_update
             BEFORE UPDATE ON electoral_constituencies
             FOR EACH ROW
             WHEN (
                 (NEW.county_id IS NULL AND NEW.special_category_id IS NULL)
                 OR
                 (NEW.county_id IS NOT NULL AND NEW.special_category_id IS NOT NULL)
             )
             BEGIN
                 SELECT RAISE(ABORT, "Exactly one electoral parent is required");
             END'
        );
    }

    public function down(): void
    {
        if (
            DB::table('electoral_constituencies')
                ->whereNotNull('special_category_id')
                ->exists()
        ) {
            throw new \RuntimeException(
                'Cannot roll back: special-category constituencies exist.'
            );
        }

        DB::statement(
            'DROP TRIGGER IF EXISTS electoral_constituencies_parent_update'
        );

        DB::statement(
            'DROP TRIGGER IF EXISTS electoral_constituencies_parent_insert'
        );

        Schema::table('electoral_constituencies', function (Blueprint $table) {
            $table->dropConstrainedForeignId('special_category_id');

            $table->foreignId('county_id')
                ->nullable(false)
                ->change();
        });

        Schema::dropIfExists('electoral_special_categories');
    }
};
