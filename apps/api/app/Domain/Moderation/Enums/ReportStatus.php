<?php

namespace App\Domain\Moderation\Enums;

enum ReportStatus: string
{
    case Pending = 'pending';
    case Dismissed = 'dismissed';

    public function label(): string
    {
        return match ($this) {
            self::Pending => 'En attente',
            self::Dismissed => 'Traité',
        };
    }
}
