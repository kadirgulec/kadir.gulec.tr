<?php

namespace App\Models\Concerns;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\DB;

/**
 * Manual order for drag-and-drop lists, in sort_order unless the model names
 * another column in sortColumn(). The model may narrow the siblings (e.g. the
 * images of one project) in sortSiblings().
 */
trait Sortable
{
    protected function sortColumn(): string
    {
        return 'sort_order';
    }

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
        $column = $this->sortColumn();

        DB::transaction(function () use ($position, $column): void {
            $ids = $this->sortSiblings(static::query())
                ->orderBy($column)
                ->orderBy($this->getKeyName())
                ->pluck($this->getKeyName())
                ->reject(fn (mixed $id): bool => $id == $this->getKey())
                ->values()
                ->all();

            array_splice($ids, max(0, min($position, count($ids))), 0, [$this->getKey()]);

            foreach ($ids as $order => $id) {
                static::query()->whereKey($id)->update([$column => $order]);
            }
        });
    }

    /**
     * The next free position at the end of the list.
     */
    public function nextSortOrder(): int
    {
        return (int) $this->sortSiblings(static::query())->max($this->sortColumn()) + 1;
    }
}
