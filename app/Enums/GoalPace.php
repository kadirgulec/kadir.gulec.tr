<?php

namespace App\Enums;

use Carbon\CarbonInterface;

/**
 * "Am I on track?" for a numeric yearly goal: progress compared with how much
 * of the year has already passed.
 */
enum GoalPace: string
{
    case Ahead = 'ahead';
    case OnTrack = 'on-track';
    case Behind = 'behind';

    /** Progress may lead the calendar by this share before it counts as ahead. */
    private const AHEAD_MARGIN = 0.05;

    /** Progress may trail the calendar by this share before it counts as behind. */
    private const BEHIND_MARGIN = 0.10;

    public static function evaluate(float $current, float $target, CarbonInterface $today): self
    {
        $progressShare = $target > 0 ? min(1, $current / $target) : 1;
        $difference = $progressShare - self::yearShare($today);

        return match (true) {
            $progressShare >= 1, $difference > self::AHEAD_MARGIN => self::Ahead,
            $difference < -self::BEHIND_MARGIN => self::Behind,
            default => self::OnTrack,
        };
    }

    /**
     * Share of the year that has passed by the end of the given day (0..1).
     */
    public static function yearShare(CarbonInterface $today): float
    {
        return $today->dayOfYear / ($today->isLeapYear() ? 366 : 365);
    }

    public function label(): string
    {
        return match ($this) {
            self::Ahead => 'önde',
            self::OnTrack => 'yolunda',
            self::Behind => 'biraz geride',
        };
    }
}
