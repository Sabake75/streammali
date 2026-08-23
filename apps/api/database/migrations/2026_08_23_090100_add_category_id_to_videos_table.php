<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * category_id is nullable for now — a separate migration makes it
     * required once this one has been deployed and verified. Splitting it
     * this way keeps each deploy step reversible.
     *
     * The old `category` column is renamed rather than left in place: a raw
     * DB column named `category` would shadow the new `category()`
     * relationship on the Video model (Eloquent checks real attributes
     * before relationship accessors of the same name), silently breaking
     * every `$video->category` access. Dropped for good in the next
     * migration.
     */
    public function up(): void
    {
        Schema::table('videos', function (Blueprint $table) {
            $table->renameColumn('category', 'category_legacy');
        });

        // The app stops writing to this column as of this deploy — nullable
        // so inserts don't fail while it's still around (dropped in the
        // next migration, once this step is confirmed working).
        Schema::table('videos', function (Blueprint $table) {
            $table->string('category_legacy')->nullable()->change();
        });

        Schema::table('videos', function (Blueprint $table) {
            // restrictOnDelete rather than nullOnDelete: the next migration
            // makes this column required, so a category in use must not be
            // deletable out from under its videos.
            $table->foreignId('category_id')->nullable()->after('category_legacy')->constrained()->restrictOnDelete();
        });

        $categoryIdsBySlug = DB::table('categories')->pluck('id', 'slug');

        foreach ($categoryIdsBySlug as $slug => $categoryId) {
            DB::table('videos')->where('category_legacy', $slug)->update(['category_id' => $categoryId]);
        }
    }

    public function down(): void
    {
        Schema::table('videos', function (Blueprint $table) {
            $table->dropConstrainedForeignId('category_id');
        });

        Schema::table('videos', function (Blueprint $table) {
            $table->renameColumn('category_legacy', 'category');
        });
    }
};
