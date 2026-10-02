<?php

namespace App\Actions\Goals;

use App\Enums\GoalKind;
use App\Models\Goal;
use Illuminate\Support\Facades\DB;

/**
 * Carries chosen goals of last year into a new year: same title, measure,
 * target, milestones (undone) and parent, but no progress.
 */
class CopyYearlyGoals
{
    /**
     * @param  list<int>  $goalIds
     * @return list<Goal>
     */
    public function handle(array $goalIds, int $year): array
    {
        $sources = Goal::query()->ofKind(GoalKind::Yearly)->whereKey($goalIds)->where('year', '<', $year)->with('milestones')->orderBy('sort_order')->get();

        return DB::transaction(function () use ($sources, $year): array {
            $copies = [];

            foreach ($sources as $source) {
                $copy = new Goal([
                    'kind' => GoalKind::Yearly,
                    'title' => $source->title,
                    'visibility' => $source->visibility,
                    'parent_id' => $source->parent_id,
                    'year' => $year,
                    'measure' => $source->measure,
                    'target' => $source->target,
                    'unit' => $source->unit,
                    'show_progress_notes' => $source->show_progress_notes,
                ]);
                $copy->sort_order = $copy->nextSortOrder();
                $copy->save();

                foreach ($source->milestones as $milestone) {
                    $copy->milestones()->create(['title' => $milestone->title, 'sort_order' => $milestone->sort_order]);
                }

                $copies[] = $copy;
            }

            return $copies;
        });
    }
}
