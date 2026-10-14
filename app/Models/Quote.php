<?php

namespace App\Models;

use App\Models\Concerns\HasPublication;
use App\Models\Concerns\Sortable;
use Carbon\CarbonImmutable;
use Database\Factories\QuoteFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\MorphTo;

/**
 * A quote collected from a book or a film/series, shown as a post-it on its
 * page. Who said it and where ("%34", "s. 143", "S2 · B5") are optional.
 *
 * @property int $id
 * @property string $quotable_type
 * @property int $quotable_id
 * @property string $body
 * @property string|null $said_by
 * @property string|null $location
 * @property int $sort_order
 * @property CarbonImmutable|null $created_at
 * @property CarbonImmutable|null $updated_at
 */
#[Fillable(['body', 'said_by', 'location', 'published_at'])]
class Quote extends Model
{
    /** @use HasFactory<QuoteFactory> */
    use HasFactory, HasPublication, Sortable;

    protected static function booted(): void
    {
        // A new quote goes to the end of its book's or film's collection.
        static::creating(function (Quote $quote): void {
            $quote->sort_order = $quote->nextSortOrder();
        });
    }

    /**
     * @return MorphTo<Model, $this>
     */
    public function quotable(): MorphTo
    {
        return $this->morphTo();
    }

    /**
     * @param  Builder<static>  $query
     * @return Builder<static>
     */
    protected function sortSiblings(Builder $query): Builder
    {
        return $query->where('quotable_type', $this->quotable_type)->where('quotable_id', $this->quotable_id);
    }

    protected function casts(): array
    {
        return [
            'sort_order' => 'integer',
        ];
    }
}
