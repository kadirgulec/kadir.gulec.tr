<?php

namespace App\Enums;

/**
 * The three columns of a monthly review.
 */
enum ReviewItemKind: string
{
    case Good = 'good';
    case Hard = 'hard';
    case Try = 'try';

    public function label(): string
    {
        return match ($this) {
            self::Good => 'İyi giden',
            self::Hard => 'Zorlandığım',
            self::Try => 'Deneyeceğim',
        };
    }

    public function sign(): string
    {
        return match ($this) {
            self::Good => '+',
            self::Hard => '–',
            self::Try => '→',
        };
    }
}
