<?php

namespace App\Domain\Account\Actions;

use App\Domain\Moderation\Enums\AccountStatus;
use App\Domain\Payment\Actions\GetCreatorBalance;
use App\Enums\UserRole;
use App\Models\User;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

/**
 * Self-service account deletion. Purchases/ledger entries/payouts are kept
 * as-is — they're accounting records, not this user's personal data to
 * withdraw — but every direct personal-info column is scrubbed and the
 * account is locked out via AccountStatus::Deleted (same mechanism as a
 * moderator suspending/blocking a user, see EnsureAccountIsActive).
 */
class DeleteAccount
{
    public function __construct(private GetCreatorBalance $getCreatorBalance) {}

    public function __invoke(User $user): void
    {
        if ($user->role === UserRole::Creator && ($this->getCreatorBalance)($user) > 0) {
            throw ValidationException::withMessages([
                'account' => ['Retirez votre solde disponible avant de supprimer votre compte.'],
            ]);
        }

        $user->tokens()->delete();

        if ($user->identity_document_path !== null) {
            Storage::disk(config('filesystems.default'))->delete($user->identity_document_path);
        }

        $user->forceFill([
            'name' => 'Compte supprimé',
            'email' => null,
            // Nullable + unique — freeing it up lets someone else register
            // with the same number later.
            'phone' => null,
            'password' => Str::random(40),
            'identity_document_path' => null,
            'account_status' => AccountStatus::Deleted,
            'account_status_reason' => 'Suppression demandée par l\'utilisateur.',
        ])->save();
    }
}
