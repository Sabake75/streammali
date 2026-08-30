<?php

namespace App\Http\Controllers\Api\Creator;

use App\Domain\Creator\Actions\UpgradeToCreator;
use App\Enums\UserRole;
use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class UpgradeController extends Controller
{
    public function store(Request $request, UpgradeToCreator $upgradeToCreator): JsonResponse
    {
        $user = $request->user();

        abort_if($user->role === UserRole::Creator, 409, 'Ce compte est déjà un compte créateur.');
        abort_if($user->role === UserRole::Moderator, 403);

        $request->validate([
            'identity_document' => ['required', 'file', 'mimes:jpg,jpeg,png,pdf', 'max:10240'],
            'terms_accepted' => ['required', 'accepted'],
        ]);

        $upgraded = $upgradeToCreator($user, $request->file('identity_document'));

        return response()->json([
            'user' => [
                'id' => $upgraded->id,
                'name' => $upgraded->name,
                'phone' => $upgraded->phone,
                'role' => $upgraded->role->value,
            ],
        ]);
    }
}
