<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Deliberately separate from 2026_08_23_090100 (which added the nullable
     * FK, renamed the old `category` column to `category_legacy`, and
     * backfilled it) — deployed once that step is confirmed working, so a
     * bad migration here doesn't also take down the column addition.
     */
    public function up(): void
    {
        Schema::table('videos', function (Blueprint $table) {
            $table->dropColumn('category_legacy');
        });

        Schema::table('videos', function (Blueprint $table) {
            $table->foreignId('category_id')->nullable(false)->change();
        });
    }

    public function down(): void
    {
        Schema::table('videos', function (Blueprint $table) {
            $table->foreignId('category_id')->nullable()->change();
            $table->string('category_legacy')->nullable()->after('description');
        });
    }
};
