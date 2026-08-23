<?php

namespace App\Http\Controllers\Api\Creator;

use App\Domain\Creator\Actions\UploadVideo;
use App\Enums\UserRole;
use App\Http\Controllers\Controller;
use App\Http\Resources\CreatorVideoResource;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

class VideoController extends Controller
{
    public function index(Request $request): AnonymousResourceCollection
    {
        $this->authorizeCreator($request);

        $videos = $request->user()->videos()->with('category')->latest()->paginate(15);

        return CreatorVideoResource::collection($videos);
    }

    public function store(Request $request, UploadVideo $uploadVideo): CreatorVideoResource
    {
        $this->authorizeCreator($request);

        $validated = $request->validate([
            'title' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string'],
            'category' => ['required', 'string', 'exists:categories,slug'],
            'poster_path' => ['nullable', 'string', 'max:2048'],
            'duration_seconds' => ['nullable', 'integer', 'min:1'],
            'price' => ['nullable', 'integer', 'min:0'],
        ]);

        $video = $uploadVideo($request->user(), $validated);

        return new CreatorVideoResource($video);
    }

    private function authorizeCreator(Request $request): void
    {
        abort_unless(
            $request->user()->role === UserRole::Creator,
            403,
            'Seuls les créateurs peuvent gérer des vidéos.',
        );
    }
}
