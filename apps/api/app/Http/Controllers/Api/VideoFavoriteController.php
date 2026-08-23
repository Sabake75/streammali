<?php

namespace App\Http\Controllers\Api;

use App\Domain\Video\Models\Video;
use App\Domain\Viewer\Actions\ToggleFavorite;
use App\Http\Controllers\Controller;
use App\Http\Resources\VideoResource;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

class VideoFavoriteController extends Controller
{
    public function store(Request $request, Video $video, ToggleFavorite $toggleFavorite): JsonResponse
    {
        $favorited = $toggleFavorite($request->user(), $video);

        return response()->json(['favorited' => $favorited]);
    }

    public function index(Request $request): AnonymousResourceCollection
    {
        $videos = Video::query()
            ->approved()
            ->with(['creator', 'category'])
            ->withAvg('reviews', 'rating')
            ->withCount('reviews')
            ->whereHas('favorites', fn ($query) => $query->where('user_id', $request->user()->id))
            ->latest()
            ->paginate(15);

        return VideoResource::collection($videos);
    }
}
