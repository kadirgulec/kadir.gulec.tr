<?php

namespace App\Models\Concerns;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\DB;

/**
 * Manual order in a sort_order column, for drag-and-drop lists. The model
 * may narrow the siblings (e.g. the images of one project) in sortSiblings().
 *
 * @property int $sort_order
 */
trait Sortable
{
    /**
     * @param  Builder<static>  $query
     * @return Builder<static>
     */
    protected function sortSiblings(Builder $query): Builder
    {
        return $query;
    }

    /**
     * Moves the row to a zero-based position and renumbers its siblings.
     */
    public function moveTo(int $position): void
    {
        DB::transaction(function () use ($position): void {
            $ids = $this->sortSiblings(static::query())
                ->orderBy('sort_order')
                ->orderBy($this->getKeyName())
                ->pluck($this->getKeyName())
                ->reject(fn (mixed $id): bool => $id == $this->getKey())
                ->values()
                ->all();

            array_splice($ids, max(0, min($position, count($ids))), 0, [$this->getKey()]);

            foreach ($ids as $order => $id) {
                static::query()->whereKey($id)->update(['sort_order' => $order]);
            }
        });
    }

    /**
     * The next free position at the end of the list.
     */
    public function nextSortOrder(): int
    {
        return (int) $this->sortSiblings(static::query())->max('sort_order') + 1;
    }
}
