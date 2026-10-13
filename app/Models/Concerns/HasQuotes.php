<?php

namespace App\Models\Concerns;

use App\Models\Quote;
use Illuminate\Database\Eloquent\Relations\MorphMany;

/**
 * Quotes collected from this book or film/series, in Kadir's order.
 */
trait HasQuotes
{
    public static function bootHasQuotes(): void
    {
        static::deleting(function ($model): void {
            $model->quotes()->delete();
        });
    }

    /**
     * @return MorphMany<Quote, $this>
     */
    public function quotes(): MorphMany
    {
        return $this->morphMany(Quote::class, 'quotable')->orderBy('sort_order')->orderBy('id');
    }
}
