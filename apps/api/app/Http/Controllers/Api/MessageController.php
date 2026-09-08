<?php

namespace App\Http\Controllers\Api;

use App\Domain\Moderation\Actions\SendMessage;
use App\Enums\UserRole;
use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * One message thread per non-moderator user with the moderation team —
 * shared by creators (originally the only ones with a support channel)
 * and viewers alike, not two separate systems.
 */
class MessageController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $this->authorizeNonModerator($request);

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
        $this->authorizeNonModerator($request);

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

    private function authorizeNonModerator(Request $request): void
    {
        abort_if(
            $request->user()->role === UserRole::Moderator,
            403,
            'La messagerie avec la modération est réservée aux créateurs et spectateurs.',
        );
    }
}
