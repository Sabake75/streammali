<?php

namespace App\Console\Commands;

use App\Domain\Video\Actions\CreatePreviewClip;
use App\Domain\Video\Contracts\VideoStorageGateway;
use App\Domain\Video\Enums\VideoSourceStatus;
use App\Domain\Video\Models\Video;
use Illuminate\Console\Command;

/**
 * Backfill for videos that went "ready" on Cloudflare Stream before the
 * webhook (App\Http\Controllers\Api\CloudflareStreamWebhookController) was
 * actually registered on Cloudflare's dashboard — preview clips and the
 * default poster (Cloudflare's own auto-generated thumbnail) only ever get
 * set from that webhook path, so any such video is stuck without either
 * permanently, with no automatic retry. Safe to re-run: both operations are
 * idempotent, and this only ever selects videos still missing one or the
 * other.
 */
class CreateMissingPreviewClips extends Command
{
    protected $signature = 'videos:create-missing-previews {video? : A single video ID, otherwise every ready video missing a preview and/or a poster}';

    protected $description = "Crée le clip d'aperçu et/ou la miniature manquants pour les vidéos déjà prêtes sur Cloudflare Stream";

    public function handle(CreatePreviewClip $createPreviewClip, VideoStorageGateway $gateway): int
    {
        $query = Video::query()
            ->where('source_status', VideoSourceStatus::Ready)
            ->where(fn ($q) => $q->whereNull('preview_provider_video_id')->orWhereNull('poster_path'));

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

            if ($video->preview_provider_video_id === null) {
                try {
                    $createPreviewClip($video);
                    $this->info('  → aperçu créé.');
                } catch (\Throwable $e) {
                    $this->error("  → échec aperçu : {$e->getMessage()}");
                }
            }

            if ($video->poster_path === null) {
                try {
                    $state = $gateway->fetchState($video);
                    $video->update(['poster_path' => $state->posterUrl]);
                    $this->info('  → miniature définie.');
                } catch (\Throwable $e) {
                    $this->error("  → échec miniature : {$e->getMessage()}");
                }
            }
        }

        return self::SUCCESS;
    }
}
