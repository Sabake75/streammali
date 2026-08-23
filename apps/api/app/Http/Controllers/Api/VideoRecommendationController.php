<?php

namespace App\Http\Controllers\Api;

use App\Domain\Viewer\Actions\GetRecommendedVideos;
use App\Http\Controllers\Controller;
use App\Http\Resources\VideoResource;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

class VideoRecommendationController extends Controller
{
    public function index(Request $request, GetRecommendedVideos $getRecommendedVideos): AnonymousResourceCollection
    {
        // Nullable guard: guests get popularity-only recommendations
        // instead of a 401 — see GetRecommendedVideos.
        $videos = $getRecommendedVideos($request->user('sanctum'));

        return VideoResource::collection($videos);
    }
}
