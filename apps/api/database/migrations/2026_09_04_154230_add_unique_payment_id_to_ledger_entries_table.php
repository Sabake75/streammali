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
        // Hard safety net against ConfirmPayment double-crediting a sale if
        // the same payment gets confirmed twice concurrently (Orange/PayDunya
        // webhook retried, or a webhook racing the success-page poll) — NULL
        // stays allowed for any future non-sale ledger entry type, since a
        // unique index treats multiple NULLs as distinct in both Postgres
        // and SQLite.
        Schema::table('ledger_entries', function (Blueprint $table) {
            $table->unique('payment_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('ledger_entries', function (Blueprint $table) {
            $table->dropUnique(['payment_id']);
        });
    }
};
