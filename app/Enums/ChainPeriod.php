<?php

namespace App\Enums;

use Carbon\CarbonImmutable;

/**
 * What one link of a chain stands for. Days are always marked one by one;
 * a weekly or monthly chain adds them up into links of that length.
 */
enum ChainPeriod: string
{
    case Day = 'day';
    case Week = 'week';
    case Month = 'month';

    public function label(): string
    {
        return match ($this) {
            self::Day => 'Her gün',
            self::Week => 'Haftalık',
            self::Month => 'Aylık',
        };
    }

    /**
     * The noun after a count: "🔥 11 hafta".
     */
    public function unit(): string
    {
        return match ($this) {
            self::Day => 'gün',
            self::Week => 'hafta',
            self::Month => 'ay',
        };
    }

    /**
     * The count as an object: "7 günü geçti", "4 haftayı geçti".
     */
    public function accusative(int $count): string
    {
        return $count.' '.match ($this) {
            self::Day => 'günü',
            self::Week => 'haftayı',
            self::Month => 'ayı',
        };
    }

    /**
     * "23 günlük seri", "11 haftalık seri".
     */
    public function adjective(): string
    {
        return match ($this) {
            self::Day => 'günlük',
            self::Week => 'haftalık',
            self::Month => 'aylık',
        };
    }

    /**
     * "23. gündeyim", "11. haftadayım".
     */
    public function atCount(int $count): string
    {
        return $count.'. '.match ($this) {
            self::Day => 'gündeyim',
            self::Week => 'haftadayım',
            self::Month => 'aydayım',
        };
    }

    /**
     * How often, for a card: "her gün", "haftada 2", "ayda 4".
     */
    public function cadence(int $target): string
    {
        return match ($this) {
            self::Day => 'her gün',
            self::Week => 'haftada '.$target,
            self::Month => 'ayda '.$target,
        };
    }

    /**
     * The most a link can ask for: every day of the shortest period.
     */
    public function maxTarget(): int
    {
        return match ($this) {
            self::Day => 1,
            self::Week => 7,
            self::Month => 28,
        };
    }

    public function start(CarbonImmutable $date): CarbonImmutable
    {
        return match ($this) {
            self::Day => $date->startOfDay(),
            self::Week => $date->startOfDay()->subDays($date->dayOfWeekIso - 1),
            self::Month => $date->startOfMonth(),
        };
    }

    /**
     * The last day of the period that starts on $start.
     */
    public function end(CarbonImmutable $start): CarbonImmutable
    {
        return match ($this) {
            self::Day => $start,
            self::Week => $start->addDays(6),
            self::Month => $start->endOfMonth()->startOfDay(),
        };
    }

    public function next(CarbonImmutable $start): CarbonImmutable
    {
        return $this->end($start)->addDay();
    }

    /**
     * Streak lengths worth an e-mail to followers.
     *
     * @return list<int>
     */
    public function milestones(): array
    {
        return match ($this) {
            self::Day => [30, 100, 365],
            self::Week => [10, 26, 52],
            self::Month => [6, 12, 24],
        };
    }

    /**
     * A new record is only news after a real streak.
     */
    public function recordMinimum(): int
    {
        return match ($this) {
            self::Day => 7,
            self::Week => 4,
            self::Month => 3,
        };
    }
}
