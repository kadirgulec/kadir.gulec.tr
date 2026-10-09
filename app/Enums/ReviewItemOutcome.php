<?php

namespace App\Enums;

/**
 * How a "try" item of last month's review turned out.
 */
enum ReviewItemOutcome: string
{
    case Done = 'done';
    case NotDone = 'not-done';

    public function label(): string
    {
        return match ($this) {
            self::Done => 'yaptım',
            self::NotDone => 'olmadı',
        };
    }
}
