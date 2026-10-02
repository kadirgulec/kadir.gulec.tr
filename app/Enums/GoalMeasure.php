<?php

namespace App\Enums;

/**
 * How a yearly goal is measured.
 */
enum GoalMeasure: string
{
    case Numeric = 'numeric';
    case Milestones = 'milestones';
    case Binary = 'binary';

    public function label(): string
    {
        return match ($this) {
            self::Numeric => 'Sayısal (7 / 12)',
            self::Milestones => 'Kilometre taşları',
            self::Binary => 'Evet / hayır',
        };
    }
}
