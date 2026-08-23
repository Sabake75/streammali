<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('videos', function (Blueprint $table) {
            $table->string('preview_provider_video_id')->nullable()->after('provider_video_id');
            $table->string('preview_playback_url')->nullable()->after('playback_url');
        });
    }

    public function down(): void
    {
        Schema::table('videos', function (Blueprint $table) {
            $table->dropColumn(['preview_provider_video_id', 'preview_playback_url']);
        });
    }
};
