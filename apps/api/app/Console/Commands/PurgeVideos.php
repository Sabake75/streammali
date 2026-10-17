<?php

namespace App\Console\Commands;

use App\Domain\Video\Models\Video;
use Illuminate\Console\Command;

/**
 * One-off cleanup for test/debug video uploads (e.g. the TUS upload audit
 * videos) cluttering a creator's own video list. Dry-run by default;
 * `--force` actually deletes. Deleting a Video cascades onto its payments/
 * reviews/favorites/reports (see the migrations) — refuses any video that
 * still has a Payment attached rather than silently destroying purchase
 * history, same safety rule as payments:purge-before-today.
 */
class PurgeVideos extends Command
{
    protected $signature = 'videos:purge {ids* : IDs des vidéos à supprimer} {--force : Supprime réellement (sans ce flag, affiche seulement un rapport)}';

    protected $description = 'Supprime des vidéos de test par ID, en refusant celles qui ont au moins un paiement associé';

    public function handle(): int
    {
        $ids = $this->argument('ids');
        $videos = Video::query()->withCount('payments')->whereIn('id', $ids)->get();

        $foundIds = $videos->pluck('id')->all();
        foreach (array_diff($ids, $foundIds) as $missingId) {
            $this->warn("Vidéo #{$missingId} introuvable, ignorée.");
        }

        if ($videos->isEmpty()) {
            $this->info('Rien à supprimer.');

            return self::SUCCESS;
        }

        $this->table(
            ['ID', 'Titre', 'Statut', 'Paiements'],
            $videos->map(fn ($v) => [$v->id, $v->title, $v->status->value, $v->payments_count]),
        );

        $eligible = $videos->filter(fn ($v) => $v->payments_count === 0);
        $blocked = $videos->filter(fn ($v) => $v->payments_count > 0);

        foreach ($blocked as $video) {
            $this->error("Vidéo #{$video->id} ({$video->title}) a {$video->payments_count} paiement(s) — ignorée.");
        }

        if ($eligible->isEmpty()) {
            $this->info('Aucune vidéo éligible à la suppression.');

            return self::SUCCESS;
        }

        if (! $this->option('force')) {
            $this->warn('Dry-run : relance avec --force pour supprimer réellement les vidéos sans paiement listées ci-dessus.');

            return self::SUCCESS;
        }

        Video::query()->whereIn('id', $eligible->pluck('id'))->delete();

        $this->info("{$eligible->count()} vidéo(s) supprimée(s).");

        return self::SUCCESS;
    }
}
