<?php

namespace App\Domain\Moderation\Enums;

enum AccountStatus: string
{
    case Active = 'active';
    case Suspended = 'suspended';
    case Blocked = 'blocked';
    // Distinct from Blocked (moderator-initiated) — set only by the user's
    // own self-service account deletion (Domain\Account\Actions\DeleteAccount).
    case Deleted = 'deleted';

    public function label(): string
    {
        return match ($this) {
            self::Active => 'Actif',
            self::Suspended => 'Suspendu',
            self::Blocked => 'Bloqué',
            self::Deleted => 'Supprimé',
        };
    }

    public function color(): string
    {
        return match ($this) {
            self::Active => 'success',
            self::Suspended => 'warning',
            self::Blocked => 'danger',
            self::Deleted => 'gray',
        };
    }
}
