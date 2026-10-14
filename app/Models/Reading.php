<?php

namespace App\Models;

use App\Actions\Goals\CountFinishedBooks;
use App\Enums\ReadingStatus;
use Carbon\CarbonImmutable;
use Database\Factories\ReadingFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * One reading of a book, from the day it was started to the day it was
 * finished or left. Progress is always a percentage (e-books have no fixed
 * page count). A reread is not stored; it is any reading with an older one before it.
 *
 * @property int $id
 * @property int $book_id
 * @property ReadingStatus $status
 * @property CarbonImmutable $started_on
 * @property CarbonImmutable|null $finished_on
 * @property int|null $progress
 * @property CarbonImmutable|null $created_at
 * @property CarbonImmutable|null $updated_at
 */
#[Fillable(['status', 'started_on', 'finished_on', 'progress'])]
class Reading extends Model
{
    /** @use HasFactory<ReadingFactory> */
    use HasFactory;

    protected static function booted(): void
    {
        // A finished book was read to the end.
        static::saving(function (Reading $reading): void {
            if ($reading->status === ReadingStatus::Finished) {
                $reading->progress = 100;
            }
        });

        // Finished, undone or moved to another year: the counting goal follows.
        static::saved(function (Reading $reading): void {
            if ($reading->wasRecentlyCreated || $reading->wasChanged(['status', 'finished_on'])) {
                app(CountFinishedBooks::class)->forReading($reading);
            }
        });

        // Started it: the book leaves the reading list.
        static::created(function (Reading $reading): void {
            if ($reading->book->isOnReadingList()) {
                $reading->book->removeFromReadingList();
            }
        });
    }

    /**
     * @return BelongsTo<Book, $this>
     */
    public function book(): BelongsTo
    {
        return $this->belongsTo(Book::class);
    }

    /**
     * The day the reading went into the diary: the last day for a finished or
     * abandoned one, the first day for one still in progress.
     */
    public function readOn(): CarbonImmutable
    {
        return $this->finished_on ?? $this->started_on;
    }

    protected function casts(): array
    {
        return [
            'status' => ReadingStatus::class,
            'started_on' => 'immutable_date',
            'finished_on' => 'immutable_date',
            'progress' => 'integer',
        ];
    }
}
