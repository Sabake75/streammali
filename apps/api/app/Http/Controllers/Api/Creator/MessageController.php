<?php

namespace App\Http\Controllers\Api\Creator;

use App\Domain\Moderation\Actions\SendMessage;
use App\Enums\UserRole;
use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class MessageController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $this->authorizeCreator($request);

        $messages = $request->user()->messages()->with('sender:id,name,role')->oldest()->get();

        return response()->json([
            'data' => $messages->map(fn ($message) => [
                'id' => $message->id,
                'body' => $message->body,
                'sender' => [
                    'id' => $message->sender->id,
                    'name' => $message->sender->name,
                    'role' => $message->sender->role->value,
                ],
                'created_at' => $message->created_at,
            ]),
        ]);
    }

    public function store(Request $request, SendMessage $sendMessage): JsonResponse
    {
        $this->authorizeCreator($request);

        $validated = $request->validate([
            'body' => ['required', 'string', 'max:2000'],
        ]);

        $message = $sendMessage($request->user(), $request->user(), $validated['body']);

        return response()->json([
            'id' => $message->id,
            'body' => $message->body,
            'sender' => [
                'id' => $request->user()->id,
                'name' => $request->user()->name,
                'role' => $request->user()->role->value,
            ],
            'created_at' => $message->created_at,
        ], 201);
    }

    private function authorizeCreator(Request $request): void
    {
        abort_unless(
            $request->user()->role === UserRole::Creator,
            403,
            'Seuls les créateurs peuvent utiliser la messagerie avec la modération.',
        );
    }
}
