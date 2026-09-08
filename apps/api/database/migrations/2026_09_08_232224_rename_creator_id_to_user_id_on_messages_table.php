<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Generalizes the moderation message thread from creator-only to any
     * non-moderator user (viewer support channel) — see App\Domain\Moderation.
     */
    public function up(): void
    {
        Schema::table('messages', function (Blueprint $table) {
            $table->renameColumn('creator_id', 'user_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('messages', function (Blueprint $table) {
            $table->renameColumn('user_id', 'creator_id');
        });
    }
};
