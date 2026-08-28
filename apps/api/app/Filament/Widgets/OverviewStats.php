<?php

namespace App\Filament\Widgets;

use App\Domain\Moderation\Enums\VideoStatus;
use App\Domain\Payment\Enums\PaymentStatus;
use App\Domain\Payment\Models\LedgerEntry;
use App\Domain\Payment\Models\Payment;
use App\Domain\Video\Models\Video;
use App\Enums\UserRole;
use App\Models\User;
use Filament\Widgets\StatsOverviewWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;

/**
 * The dashboard tab of the cahier des charges' moderator profile
 * ("Dashboard global (créateurs, viewers, ventes, chiffre d'affaires)")
 * — the panel only ever had Filament's own scaffolding widgets
 * (AccountWidget/FilamentInfoWidget) until now, no StreamMali data at all.
 */
class OverviewStats extends StatsOverviewWidget
{
    protected function getStats(): array
    {
        $revenue = Payment::where('status', PaymentStatus::Succeeded)->sum('amount');
        $commission = LedgerEntry::sum('commission_amount');
        $pendingVideos = Video::where('status', VideoStatus::Pending)->count();

        return [
            Stat::make('Créateurs', User::where('role', UserRole::Creator)->count()),
            Stat::make('Viewers', User::where('role', UserRole::Viewer)->count()),
            Stat::make('Vidéos en attente', $pendingVideos)
                ->description($pendingVideos > 0 ? 'À modérer' : null)
                ->color($pendingVideos > 0 ? 'warning' : null),
            Stat::make('Ventes', Payment::where('status', PaymentStatus::Succeeded)->count()),
            Stat::make('Chiffre d\'affaires', number_format($revenue, 0, ',', ' ').' FCFA'),
            Stat::make('Commission plateforme', number_format($commission, 0, ',', ' ').' FCFA'),
        ];
    }
}
