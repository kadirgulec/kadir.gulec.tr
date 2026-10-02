<?php

namespace App\Support;

/**
 * Numbers for a "don't break the chain" habit, computed from its days (oldest first).
 *
 * An excused day (sick, on holiday) never breaks a chain, but it does not count as done either.
 */
class ChainStats
{
    /**
     * Done days since the last missed day.
     *
     * @param  list<'done'|'missed'|'excused'>  $days
     */
    public static function currentStreak(array $days): int
    {
        $streak = 0;

        foreach (array_reverse($days) as $day) {
            if ($day === 'missed') {
                break;
            }

            if ($day === 'done') {
                $streak++;
            }
        }

        return $streak;
    }

    /**
     * The longest run of done days without a missed day in between.
     *
     * @param  list<'done'|'missed'|'excused'>  $days
     */
    public static function bestStreak(array $days): int
    {
        $best = 0;
        $run = 0;

        foreach ($days as $day) {
            $run = match ($day) {
                'done' => $run + 1,
                'missed' => 0,
                default => $run,
            };

            $best = max($best, $run);
        }

        return $best;
    }

    /**
     * @param  list<'done'|'missed'|'excused'>  $days
     */
    public static function count(array $days, string $state): int
    {
        return count(array_filter($days, fn (string $day): bool => $day === $state));
    }

    /**
     * Share of the days that counted (excused days left out), as a whole percentage.
     *
     * @param  list<'done'|'missed'|'excused'>  $days
     */
    public static function successRate(array $days): int
    {
        $countedDays = count($days) - self::count($days, 'excused');

        return $countedDays > 0 ? (int) round(self::count($days, 'done') / $countedDays * 100) : 0;
    }
}
