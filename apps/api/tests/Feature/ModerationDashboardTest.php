<?php

namespace Tests\Feature;

use App\Domain\Payment\Enums\PayoutStatus;
use App\Domain\Payment\Models\Payment;
use App\Enums\UserRole;
use App\Filament\Widgets\OverviewStats;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class ModerationDashboardTest extends TestCase
{
    use RefreshDatabase;

    public function test_the_dashboard_shows_the_main_account_balance(): void
    {
        $moderator = User::factory()->create(['role' => UserRole::Moderator]);
        $creator = User::factory()->create(['role' => UserRole::Creator]);

        // Encaissé : 10 000 FCFA de ventes. Déjà reversé : 3 000 FCFA de
        // retrait payé. Solde attendu du compte principal : 7 000 FCFA.
        Payment::factory()->create(['amount' => 10_000, 'status' => 'succeeded']);
        $creator->payouts()->create([
            'amount' => 3_000,
            'destination_msisdn' => '+223 76 00 00 00',
            'status' => PayoutStatus::Paid,
        ]);

        Livewire::actingAs($moderator)
            ->test(OverviewStats::class)
            ->assertSee('Solde du compte principal')
            ->assertSee('7 000 FCFA');
    }
}
