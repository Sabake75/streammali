<?php

namespace App\Filament\Resources\Users\Pages;

use App\Filament\Resources\Users\UserResource;
use Filament\Resources\Pages\EditRecord;

class EditUser extends EditRecord
{
    protected static string $resource = UserResource::class;

    // Pas de DeleteAction : un `DELETE` sur `users` cascaderait sur
    // videos/payments/payouts/ledger_entries/messages/reviews/favorites/
    // reports (toutes en cascadeOnDelete) — encore plus destructeur que le
    // même problème déjà retiré sur les vidéos (voir Domain\Video\README).
    // Suspendre/bloquer (déjà dans UsersTable) sont les vrais outils de
    // modération d'un compte.
    protected function getHeaderActions(): array
    {
        return [];
    }
}
