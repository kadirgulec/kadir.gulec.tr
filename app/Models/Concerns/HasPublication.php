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
