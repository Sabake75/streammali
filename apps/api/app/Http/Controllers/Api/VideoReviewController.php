<?php

namespace App\Http\Controllers\Api;

use App\Domain\Review\Actions\SubmitReview;
use App\Domain\Video\Models\Video;
use App\Http\Controllers\Controller;
use App\Http\Resources\ReviewResource;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

class VideoReviewController extends Controller
{
    public function index(Video $video): AnonymousResourceCollection
    {
        $reviews = $video->reviews()->with('user')->latest()->paginate(15);

        return ReviewResource::collection($reviews);
    }

    public function store(Request $request, Video $video, SubmitReview $submitReview): ReviewResource
    {
        abort_unless(
            $video->isPurchasedBy($request->user()),
            403,
            'Tu dois avoir acheté cette vidéo pour laisser un avis.',
        );

        $validated = $request->validate([
            'rating' => ['required', 'integer', 'min:1', 'max:5'],
            'comment' => ['nullable', 'string', 'max:2000'],
        ]);

        $review = $submitReview($video, $request->user(), $validated['rating'], $validated['comment'] ?? null);

        return new ReviewResource($review->load('user'));
    }
}
