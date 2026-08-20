<?php

namespace App\Http\Controllers\Api\Auth;

use App\Domain\Creator\Actions\RegisterCreator;
use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class RegisterCreatorController extends Controller
{
    public function store(Request $request, RegisterCreator $registerCreator): JsonResponse
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'phone' => ['required', 'string', 'max:32', 'unique:users,phone'],
            'password' => ['required', 'string', 'min:8'],
            'identity_document' => ['required', 'file', 'mimes:jpg,jpeg,png,pdf', 'max:10240'],
        ]);

        $user = $registerCreator(
            $validated['name'],
            $validated['phone'],
            $validated['password'],
            $request->file('identity_document'),
        );

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
