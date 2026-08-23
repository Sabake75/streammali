<?php

namespace App\Http\Controllers\Api\Auth;

use App\Domain\Viewer\Actions\RegisterViewer;
use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class RegisterController extends Controller
{
    public function store(Request $request, RegisterViewer $registerViewer): JsonResponse
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'phone' => ['required', 'string', 'max:32', 'unique:users,phone'],
            'password' => ['required', 'digits:4'],
            'terms_accepted' => ['required', 'accepted'],
        ]);

        $user = $registerViewer($validated['name'], $validated['phone'], $validated['password']);

        return response()->json([
            'user' => [
                'id' => $user->id,
                'name' => $user->name,
                'phone' => $user->phone,
                'role' => $user->role->value,
            ],
            'token' => $user->createToken('api')->plainTextToken,
        ], 201);
    }
}
