<?php

namespace App\Models\Concerns;

use App\Enums\PublicationState;
use Carbon\CarbonImmutable;
use Illuminate\Database\Eloquent\Attributes\Scope;
use Illuminate\Database\Eloquent\Builder;

/**
 * Draft, scheduled or published, all from one published_at column.
 * Visitors only ever see published() rows; Kadir sees drafts with a banner.
 *
 * @property CarbonImmutable|null $published_at
 */
trait HasPublication
{
    public function initializeHasPublication(): void
    {
        $this->mergeCasts(['published_at' => 'immutable_datetime']);
    }

    /**
     * @param  Builder<static>  $query
     */
    #[Scope]
    protected function published(Builder $query): void
    {
        $query->whereNotNull($this->qualifyColumn('published_at'))
            ->where($this->qualifyColumn('published_at'), '<=', now());
    }

    /**
     * Rows published after this one (at the same time: with a higher id), the nearest first.
     *
     * @param  Builder<static>  $query
     */
    #[Scope]
    protected function newerThan(Builder $query, self $row): void
    {
        $query->where(fn (Builder $query) => $query->where($this->qualifyColumn('published_at'), '>', $row->published_at)
            ->orWhere(fn (Builder $query) => $query->where($this->qualifyColumn('published_at'), $row->published_at)->where($this->getQualifiedKeyName(), '>', $row->getKey())))
            ->reorder()
            ->orderBy($this->qualifyColumn('published_at'))
            ->orderBy($this->getQualifiedKeyName());
    }

    /**
     * Rows published before this one (at the same time: with a lower id), the nearest first.
     *
     * @param  Builder<static>  $query
     */
    #[Scope]
    protected function olderThan(Builder $query, self $row): void
    {
        $query->where(fn (Builder $query) => $query->where($this->qualifyColumn('published_at'), '<', $row->published_at)
            ->orWhere(fn (Builder $query) => $query->where($this->qualifyColumn('published_at'), $row->published_at)->where($this->getQualifiedKeyName(), '<', $row->getKey())))
            ->reorder()
            ->orderByDesc($this->qualifyColumn('published_at'))
            ->orderByDesc($this->getQualifiedKeyName());
    }

    public function publicationState(): PublicationState
    {
        return match (true) {
            $this->published_at === null => PublicationState::Draft,
            $this->published_at->isFuture() => PublicationState::Scheduled,
            default => PublicationState::Published,
        };
    }

    public function isPublished(): bool
    {
        return $this->publicationState() === PublicationState::Published;
    }
}
