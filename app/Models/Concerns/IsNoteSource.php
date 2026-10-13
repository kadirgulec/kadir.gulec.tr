<?php

namespace App\Models\Concerns;

use App\Models\Note;
use Illuminate\Database\Eloquent\Relations\MorphMany;

/**
 * Notes can name this book or film/series as their source. Deleting it
 * keeps the notes; they only lose their source.
 */
trait IsNoteSource
{
    public static function bootIsNoteSource(): void
    {
        static::deleting(function ($model): void {
            $model->notes()->update(['source_type' => null, 'source_id' => null]);
        });
    }

    /**
     * @return MorphMany<Note, $this>
     */
    public function notes(): MorphMany
    {
        return $this->morphMany(Note::class, 'source');
    }
}
