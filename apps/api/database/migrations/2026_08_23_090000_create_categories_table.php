<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('categories', function (Blueprint $table) {
            $table->id();
            $table->string('slug')->unique();
            $table->string('label');
            $table->timestamps();
        });

        // Backfills the categories that used to be a hardcoded enum
        // (App\Domain\Video\Enums\VideoCategory) — see the next migration.
        $now = now();
        DB::table('categories')->insert([
            ['slug' => 'film', 'label' => 'Film', 'created_at' => $now, 'updated_at' => $now],
            ['slug' => 'clip', 'label' => 'Clip', 'created_at' => $now, 'updated_at' => $now],
            ['slug' => 'sketch', 'label' => 'Sketch', 'created_at' => $now, 'updated_at' => $now],
            ['slug' => 'series', 'label' => 'Web-série', 'created_at' => $now, 'updated_at' => $now],
        ]);
    }

    public function down(): void
    {
        Schema::dropIfExists('categories');
    }
};
