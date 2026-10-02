<?php

namespace App\Enums;

/**
 * A marked chain day. A day without a row is a missed day.
 */
enum ChainDayState: string
{
    case Done = 'done';
    case Excused = 'excused';

    /**
     * The next state when a day in the grid is clicked: missed → done → excused → missed.
     */
    public static function next(?self $state): ?self
    {
        return match ($state) {
            null => self::Done,
            self::Done => self::Excused,
            self::Excused => null,
        };
    }
}
