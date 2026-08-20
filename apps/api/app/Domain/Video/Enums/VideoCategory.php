<?php

namespace App\Domain\Video\Enums;

enum VideoCategory: string
{
    case Film = 'film';
    case Clip = 'clip';
    case Sketch = 'sketch';
    case Series = 'series';

    public function label(): string
    {
        return match ($this) {
            self::Film => 'Film',
            self::Clip => 'Clip',
            self::Sketch => 'Sketch',
            self::Series => 'Web-série',
        };
    }
}
