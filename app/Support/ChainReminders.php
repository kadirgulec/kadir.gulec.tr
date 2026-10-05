<?php

namespace App\Support;

use App\Enums\ChainPeriod;
use App\Models\Goal;
use Carbon\CarbonImmutable;

/**
 * When a chain should remind Kadir (only him, not its followers).
 *
 *  - Weekly and monthly chains, in the morning: once the days left in the
 *    period are at most twice the times still needed ("2 more in 4 days"),
 *    i.e. from now on every other day has to count. One reminder per
 *    period and number still needed, so doing one more moves to the next.
 *  - Monthly chains also get a pace check from the 15th: less than half
 *    of the target reached by the middle of the month.
 *  - Daily chains, in the evening: today is not marked yet.
 *
 * Excused days count toward the target, as they do for the link itself.
 */
class ChainReminders
{
    /** Days left per time still needed at which the reminder starts. */
    public const DAYS_PER_TIME = 2;

    /** The day of the month from which the pace check runs. */
    public const PACE_CHECK_DAY = 15;

    /**
     * @param  array{needed: int, daysLeft: int, ...}  $period  from Goal::chainPeriodAt()
     */
    public static function isDue(array $period): bool
    {
        return $period['needed'] > 0 && $period['daysLeft'] <= self::DAYS_PER_TIME * $period['needed'];
    }

    /**
     * @return array{key: string, text: string}|null
     */
    public static function morning(Goal $chain, ?CarbonImmutable $today = null): ?array
    {
        if ($chain->chain_period === ChainPeriod::Day) {
            return null;
        }

        $today ??= CarbonImmutable::today();
        $period = $chain->chainPeriodAt($today);
        $start = $period['start']->toDateString();
        $progress = self::progress($chain, $period);

        // A chain started on a Sunday cannot be asked for two times that week.
        if ($period['needed'] === 0 || ($period['partial'] && $period['needed'] > $period['daysLeft'])) {
            return null;
        }

        if (self::isDue($period)) {
            return [
                'key' => 'due-'.$start.'-'.$period['needed'],
                'text' => $period['needed'] > $period['daysLeft']
                    ? $progress.', '.$period['needed'].' kez lazım ama '.$period['daysLeft'].' gün kaldı'
                    : $progress.', '.$period['daysLeft'].' günde '.$period['needed'].' kez daha',
            ];
        }

        $behind = ($period['done'] + $period['excused']) * 2 < $period['target'];

        if ($chain->chain_period === ChainPeriod::Month && $today->day >= self::PACE_CHECK_DAY && $behind) {
            return ['key' => 'pace-'.$start, 'text' => 'ayın yarısı geçti, '.$progress];
        }

        return null;
    }

    /**
     * @return array{key: string, text: string}|null
     */
    public static function evening(Goal $chain, ?CarbonImmutable $today = null): ?array
    {
        $today ??= CarbonImmutable::today();

        if ($chain->chain_period !== ChainPeriod::Day || $chain->chainDays->contains(fn ($day): bool => $day->date->isSameDay($today))) {
            return null;
        }

        return ['key' => 'evening-'.$today->toDateString(), 'text' => 'bugün henüz işaretlenmedi'];
    }

    /**
     * "bu hafta 1/2", "bu ay 0/4".
     *
     * @param  array{done: int, excused: int, target: int, ...}  $period
     */
    public static function progress(Goal $chain, array $period): string
    {
        return 'bu '.$chain->chain_period->unit().' '.($period['done'] + $period['excused']).'/'.$period['target'];
    }
}
