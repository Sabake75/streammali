<?php

namespace App\Domain\Creator\Actions;

use App\Enums\UserRole;
use App\Models\User;
use Illuminate\Http\UploadedFile;

/**
 * Turns an existing viewer account into a creator account in place —
 * same phone/id/purchase history, no second disconnected account. Mirrors
 * RegisterCreator's identity-document storage exactly, minus creating a
 * new User row (and minus phone/password, already set from signup).
 */
class UpgradeToCreator
{
    public function __invoke(User $user, UploadedFile $identityDocument): User
    {
        $path = $identityDocument->store('identity-documents', config('filesystems.default'));

        $user->forceFill([
            'role' => UserRole::Creator,
            'identity_document_path' => $path,
            // Only reached once the controller's `accepted` validation rule
            // (for the creator CGU specifically, distinct from the viewer
            // CGU already accepted at signup) has passed.
            'terms_accepted_at' => now(),
        ])->save();

        return $user;
    }
}
