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

        return $chain->chainDays()->updateOrCreate(
            ['date' => $date->toDateString()],
            ['state' => $state, 'note' => $state === ChainDayState::Excused ? $note : null],
        );
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
