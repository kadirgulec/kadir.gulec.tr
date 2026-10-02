<?php

namespace App\Actions\Goals;

use App\Enums\ChainDayState;
use App\Enums\GoalKind;
use App\Models\ChainDay;
use App\Models\Goal;
use Carbon\CarbonImmutable;
use Illuminate\Validation\ValidationException;

/**
 * Sets the state of one chain day. No state means missed (the row goes).
 * Future days and days outside the chain's run are locked.
 */
class MarkChainDay
{
    public function handle(Goal $chain, CarbonImmutable $date, ?ChainDayState $state, ?string $note = null): ?ChainDay
    {
        if ($chain->kind !== GoalKind::Chain) {
            throw ValidationException::withMessages(['date' => 'Sadece zincirlerin günleri işaretlenir.']);
        }

        $date = $date->startOfDay();

        if ($date->isFuture()) {
            throw ValidationException::withMessages(['date' => 'Gelecek bir gün işaretlenemez.']);
        }

        if (($chain->started_on !== null && $date->lessThan($chain->started_on)) || ($chain->ended_on !== null && $date->greaterThan($chain->ended_on))) {
            throw ValidationException::withMessages(['date' => 'Bu gün zincirin süresi dışında.']);
        }

        if ($state === null) {
            $chain->chainDays()->whereDate('date', $date)->delete();

            return null;
        }

        // Looked up with whereDate: SQLite stores the date as "Y-m-d H:i:s", so an exact match would miss it.
        $day = $chain->chainDays()->whereDate('date', $date)->first() ?? $chain->chainDays()->make(['date' => $date]);
        $day->fill(['state' => $state, 'note' => $state === ChainDayState::Excused ? $note : null])->save();

        return $day;
    }

    /**
     * Click in the grid: missed → done → excused → missed.
     */
    public function cycle(Goal $chain, CarbonImmutable $date): ?ChainDay
    {
        $current = $chain->chainDays()->whereDate('date', $date)->first()?->state;

        return $this->handle($chain, $date, ChainDayState::next($current));
    }

    /**
     * The one-tap button on the dashboard: tapping the active state again takes it back.
     */
    public function toggle(Goal $chain, CarbonImmutable $date, ChainDayState $state): ?ChainDay
    {
        $current = $chain->chainDays()->whereDate('date', $date)->first()?->state;

        return $this->handle($chain, $date, $current === $state ? null : $state);
    }
}
