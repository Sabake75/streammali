<?php

namespace App\Http\Controllers\Api;

use App\Domain\Moderation\Enums\VideoStatus;
use App\Domain\Video\Models\Video;
use App\Http\Controllers\Controller;
use App\Http\Resources\VideoResource;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

class VideoCatalogController extends Controller
{
    public function index(Request $request): AnonymousResourceCollection
    {
        $validated = $request->validate([
            'category' => ['nullable', 'string', 'exists:categories,slug'],
            'creator_id' => ['nullable', 'integer'],
            'search' => ['nullable', 'string', 'max:255'],
        ]);

        $videos = Video::query()
            ->approved()
            ->with(['creator', 'category'])
            ->withAvg('reviews', 'rating')
            ->withCount('reviews')
            ->when(
                $validated['category'] ?? null,
                fn ($query, $category) => $query->whereRelation('category', 'slug', $category),
            )
            ->when($validated['creator_id'] ?? null, fn ($query, $creatorId) => $query->where('creator_id', $creatorId))
            ->when($validated['search'] ?? null, fn ($query, $search) => $query->where('title', 'like', "%{$search}%"))
            ->latest()
            ->paginate(15);

        return VideoResource::collection($videos);
    }

    public function featured(): AnonymousResourceCollection
    {
        $videos = Video::query()
            ->approved()
            ->featured()
            ->with(['creator', 'category'])
            ->withAvg('reviews', 'rating')
            ->withCount('reviews')
            ->limit(12)
            ->get();

        return VideoResource::collection($videos);
    }

    public function show(Video $video): VideoResource
    {
        abort_unless($video->status === VideoStatus::Approved, 404);

        $video->loadAvg('reviews', 'rating');
        $video->loadCount('reviews');

        return new VideoResource($video->load(['creator', 'category']));
    }

    /**
     * Deliberately separate from show(): the client fetches video data
     * through a cached request (Next.js `revalidate: 60`, potentially served
     * from a CDN too), so a side effect there would silently undercount
     * every view served from cache. This endpoint is called directly by the
     * client on each real page view instead, uncached.
     */
    public function view(Video $video): JsonResponse
    {
        abort_unless($video->status === VideoStatus::Approved, 404);

        $video->increment('views_count');

        return response()->json(['views_count' => $video->views_count]);
    }
}
