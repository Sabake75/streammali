<?php

namespace App\Domain\Video\Enums;

enum VideoSourceStatus: string
{
    case NotStarted = 'not_started';
    case Processing = 'processing';
    case Ready = 'ready';
    case Failed = 'failed';

    public function label(): string
    {
        return match ($this) {
            self::NotStarted => 'Pas encore envoyée',
            self::Processing => 'En cours de traitement',
            self::Ready => 'Prête',
            self::Failed => 'Échec',
        };
    }

    public function color(): string
    {
        return match ($this) {
            self::NotStarted => 'gray',
            self::Processing => 'warning',
            self::Ready => 'success',
            self::Failed => 'danger',
        };
    }
}
