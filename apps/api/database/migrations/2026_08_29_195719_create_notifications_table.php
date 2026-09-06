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
        Schema::create('notifications', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->string('type');
            $table->morphs('notifiable');
            // json, not text: Filament's DatabaseNotifications Livewire
            // component queries ->where('data->format', 'filament'), which
            // Laravel compiles to Postgres's `data->>'format'` operator —
            // undefined on a text column. SQLite's dynamic typing hides this,
            // which is why it only surfaced against the real Postgres prod DB.
            $table->json('data');
            $table->timestamp('read_at')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('notifications');
    }
};
