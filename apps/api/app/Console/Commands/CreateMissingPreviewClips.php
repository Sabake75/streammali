<?php

namespace App\Console\Commands;

use App\Domain\Video\Actions\CreatePreviewClip;
use App\Domain\Video\Enums\VideoSourceStatus;
use App\Domain\Video\Models\Video;
use Illuminate\Console\Command;

/**
 * Backfill for videos that went "ready" on Cloudflare Stream before the
 * webhook (App\Http\Controllers\Api\CloudflareStreamWebhookController) was
 * actually registered on Cloudflare's dashboard — preview clips only ever
 * get created from that webhook path, so any such video is stuck without
 * one permanently, with no automatic retry. Safe to re-run: CreatePreviewClip
 * is idempotent, and this only ever selects videos still missing one.
 */
class CreateMissingPreviewClips extends Command
{
    protected $signature = 'videos:create-missing-previews {video? : A single video ID, otherwise every ready video missing one}';

    protected $description = "Crée le clip d'aperçu manquant pour les vidéos déjà prêtes sur Cloudflare Stream";

    public function handle(CreatePreviewClip $createPreviewClip): int
    {
        $query = Video::query()
            ->where('source_status', VideoSourceStatus::Ready)
            ->whereNull('preview_provider_video_id');

        if ($videoId = $this->argument('video')) {
            $query->where('id', $videoId);
        }

        $videos = $query->get();

        if ($videos->isEmpty()) {
            $this->info('Aucune vidéo à traiter.');

            return self::SUCCESS;
        }

        foreach ($videos as $video) {
            $this->line("Vidéo #{$video->id} ({$video->title})...");

            try {
                $createPreviewClip($video);
                $this->info('  → aperçu créé.');
            } catch (\Throwable $e) {
                $this->error("  → échec : {$e->getMessage()}");
            }
        }

        return self::SUCCESS;
    }
}
