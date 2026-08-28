<?php

namespace App\Filament\Widgets;

use App\Domain\Payment\Models\LedgerEntry;
use Filament\Widgets\ChartWidget;
use Illuminate\Support\Carbon;

/**
 * Platform-wide daily gross revenue — same date-bucketing approach as
 * App\Domain\Creator\Actions\GetCreatorStats, just not scoped to one
 * creator.
 */
class RevenueChart extends ChartWidget
{
    private const TIMESERIES_DAYS = 14;

    protected ?string $heading = 'Chiffre d\'affaires — 14 derniers jours';

    protected function getType(): string
    {
        return 'line';
    }

    protected function getData(): array
    {
        $since = Carbon::today()->subDays(self::TIMESERIES_DAYS - 1);

        $byDay = LedgerEntry::query()
            ->where('created_at', '>=', $since)
            ->selectRaw('date(created_at) as day, sum(gross_amount) as revenue')
            ->groupBy('day')
            ->pluck('revenue', 'day');

        $days = collect(range(0, self::TIMESERIES_DAYS - 1))
            ->map(fn (int $offset) => $since->copy()->addDays($offset)->toDateString());

        return [
            'datasets' => [
                [
                    'label' => 'FCFA',
                    'data' => $days->map(fn (string $date) => (int) ($byDay[$date] ?? 0))->all(),
                    'borderColor' => '#ea580c',
                    'backgroundColor' => 'rgba(234, 88, 12, 0.15)',
                    'fill' => true,
                ],
            ],
            'labels' => $days->map(fn (string $date) => Carbon::parse($date)->format('d/m'))->all(),
        ];
    }
}
