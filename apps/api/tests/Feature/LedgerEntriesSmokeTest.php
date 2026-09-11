<?php

namespace Tests\Feature;

use App\Enums\UserRole;
use App\Filament\Resources\LedgerEntries\Pages\ListLedgerEntries;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

/**
 * `php -l`/static analysis wouldn't catch a filter's ->query() closure
 * throwing at runtime (e.g. a bad whereHas() dot-path, an unregistered
 * column) — this actually renders the page and applies every filter
 * on /moderation/ledger-entries.
 */
class LedgerEntriesSmokeTest extends TestCase
{
    use RefreshDatabase;

    public function test_the_page_renders_with_every_filter_applied(): void
    {
        $moderator = User::factory()->create(['role' => UserRole::Moderator]);

        Livewire::actingAs($moderator)
            ->test(ListLedgerEntries::class)
            ->assertSuccessful()
            ->filterTable('id', ['min' => 1, 'max' => 1000])
            ->filterTable('video', ['title' => 'test'])
            ->filterTable('gross_amount', ['min' => 0, 'max' => 100000])
            ->filterTable('commission_amount', ['min' => 0, 'max' => 100000])
            ->filterTable('net_amount', ['min' => 0, 'max' => 100000])
            ->filterTable('provider_transaction_id', ['value' => 'abc'])
            ->filterTable('created_at', ['from' => '2026-01-01', 'until' => '2026-12-31'])
            ->assertSuccessful();
    }
}
