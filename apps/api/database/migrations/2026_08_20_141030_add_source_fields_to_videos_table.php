<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('videos', function (Blueprint $table) {
            $table->string('source_provider')->default('cloudflare_stream')->after('poster_path');
            $table->string('provider_video_id')->nullable()->after('source_provider');
            $table->string('source_status')->default('not_started')->after('provider_video_id');
            $table->string('playback_url')->nullable()->after('source_status');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('videos', function (Blueprint $table) {
            $table->dropColumn(['source_provider', 'provider_video_id', 'source_status', 'playback_url']);
        });
    }
};
