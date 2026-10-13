<?php

namespace App\Actions\Goals;

use App\Enums\ReadingStatus;
use App\Models\Goal;
use App\Models\GoalProgress;
use App\Models\Reading;
use Illuminate\Support\Facades\DB;

/**
 * "Okuduklarım'dan sayılsın": a finished reading is +1 on the counting goal
 * of the year it was finished in (the first one, should there be two). The
 * entry is linked to the reading, so it moves with the finish date and goes
 * when the reading is no longer finished. Rereads count, abandoned books do not.
 */
class CountFinishedBooks
{
    public function forReading(Reading $reading): void
    {
        $goal = $reading->status === ReadingStatus::Finished && $reading->finished_on !== null
            ? Goal::query()->countingBooksIn($reading->finished_on->year)->first()
            : null;

        $entry = GoalProgress::query()->where('reading_id', $reading->id)->first();

        if ($entry !== null && $entry->goal_id !== $goal?->id) {
            $entry->delete();
            $entry = null;
        }

        if ($goal === null) {
            return;
        }

        $attributes = ['date' => $reading->finished_on, 'amount' => 1, 'note' => $reading->book->title];

        if ($entry !== null) {
            $entry->update($attributes);
        } else {
            $goal->progressEntries()->create([...$attributes, 'reading_id' => $reading->id]);
        }
    }

    /**
     * After the switch was turned on or off, or the goal moved to another
     * year: its book entries are rebuilt from the readings of its year, so
     * books finished before the switch was turned on count too.
     */
    public function forGoal(Goal $goal): void
    {
        DB::transaction(function () use ($goal): void {
            $goal->progressEntries()->whereNotNull('reading_id')->delete();

            if (! $goal->counts_books || $goal->year === null) {
                return;
            }

            Reading::query()
                ->with('book')
                ->where('status', ReadingStatus::Finished)
                ->whereYear('finished_on', $goal->year)
                ->orderBy('finished_on')
                ->orderBy('id')
                ->each(fn (Reading $reading) => $this->forReading($reading));
        });
    }
}
