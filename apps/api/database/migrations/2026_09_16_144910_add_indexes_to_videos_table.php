<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Unlike MySQL, PostgreSQL (what production runs, see CLAUDE.md) does
     * not automatically index a `foreignId()->constrained()` column — so
     * creator_id/category_id had a foreign key but no index despite being
     * filtered on every catalogue request. status/featured_at/views_count
     * never had an index either, despite being filtered/sorted on every
     * VideoCatalogController::index and ::featured call. Invisible at the
     * current catalogue size, would force a sequential scan once it grows.
     */
    public function up(): void
    {
        Schema::table('videos', function (Blueprint $table) {
            $table->index('status');
            $table->index('creator_id');
            $table->index('category_id');
            $table->index('featured_at');
            $table->index('views_count');
        });
    }

    public function down(): void
    {
        Schema::table('videos', function (Blueprint $table) {
            $table->dropIndex(['status']);
            $table->dropIndex(['creator_id']);
            $table->dropIndex(['category_id']);
            $table->dropIndex(['featured_at']);
            $table->dropIndex(['views_count']);
        });
    }
};
