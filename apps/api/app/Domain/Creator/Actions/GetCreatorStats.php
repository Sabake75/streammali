<?php

namespace App\Domain\Creator\Actions;

use App\Domain\Payment\Enums\PaymentStatus;
use App\Domain\Payment\Models\LedgerEntry;
use App\Models\User;
use Illuminate\Support\Carbon;

class GetCreatorStats
{
    private const TIMESERIES_DAYS = 14;

    /**
     * @return array{
     *     videos: array<int, array{id: int, title: string, views_count: int, purchases_count: int, revenue: int}>,
     *     totals: array{views: int, purchases: int, revenue: int},
     *     timeseries: array<int, array{date: string, revenue: int}>,
     * }
     */
    public function __invoke(User $creator): array
    {
        $videos = $creator->videos()
            ->withCount(['payments as purchases_count' => fn ($query) => $query->where('status', PaymentStatus::Succeeded)])
            ->get();

        $revenueByVideo = LedgerEntry::query()
            ->where('creator_id', $creator->id)
            ->whereNotNull('payment_id')
            ->join('payments', 'payments.id', '=', 'ledger_entries.payment_id')
            ->selectRaw('payments.video_id, sum(ledger_entries.net_amount) as revenue')
            ->groupBy('payments.video_id')
            ->pluck('revenue', 'video_id');

        $videoStats = $videos->map(fn ($video) => [
            'id' => $video->id,
            'title' => $video->title,
            'views_count' => $video->views_count,
            'purchases_count' => $video->purchases_count,
            'revenue' => (int) ($revenueByVideo[$video->id] ?? 0),
        ]);

        return [
            'videos' => $videoStats->values()->all(),
            'totals' => [
                'views' => $videoStats->sum('views_count'),
                'purchases' => $videoStats->sum('purchases_count'),
                'revenue' => $videoStats->sum('revenue'),
            ],
            'timeseries' => $this->revenueTimeseries($creator),
        ];
    }

    /**
     * @return array<int, array{date: string, revenue: int}>
     */
    private function revenueTimeseries(User $creator): array
    {
        $since = Carbon::today()->subDays(self::TIMESERIES_DAYS - 1);

        $byDay = LedgerEntry::query()
            ->where('creator_id', $creator->id)
            ->where('created_at', '>=', $since)
            ->selectRaw('date(created_at) as day, sum(net_amount) as revenue')
            ->groupBy('day')
            ->pluck('revenue', 'day');

        return collect(range(0, self::TIMESERIES_DAYS - 1))
            ->map(function (int $offset) use ($since, $byDay) {
                $date = $since->copy()->addDays($offset)->toDateString();

                return [
                    'date' => $date,
                    'revenue' => (int) ($byDay[$date] ?? 0),
                ];
            })
            ->all();
    }
}
