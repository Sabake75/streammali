<?php

namespace App\Http\Controllers\Api;

use App\Domain\Account\Actions\DeleteAccount;
use App\Domain\Account\Actions\ExportAccountData;
use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class AccountController extends Controller
{
    public function export(Request $request, ExportAccountData $exportAccountData): JsonResponse
    {
        return response()->json($exportAccountData($request->user()))
            ->header('Content-Disposition', 'attachment; filename="streammali-donnees.json"');
    }

    public function destroy(Request $request, DeleteAccount $deleteAccount): JsonResponse
    {
        $deleteAccount($request->user());

        return response()->json(['message' => 'Compte supprimé.']);
    }
}
