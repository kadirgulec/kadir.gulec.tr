<?php

namespace App\Models;

use Carbon\CarbonImmutable;
use Database\Factories\SeasonFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * A season of a series: its episode count (from TMDB) and an optional mark and note.
 *
 * @property int $id
 * @property int $watchable_id
 * @property int $number
 * @property int $episode_count
 * @property float|null $rating
 * @property string|null $note
 * @property CarbonImmutable|null $created_at
 * @property CarbonImmutable|null $updated_at
 */
#[Fillable(['number', 'episode_count', 'rating', 'note'])]
class Season extends Model
{
    /** @use HasFactory<SeasonFactory> */
    use HasFactory;

    /**
     * @return BelongsTo<Watchable, $this>
     */
    public function watchable(): BelongsTo
    {
        return $this->belongsTo(Watchable::class);
    }

    protected function casts(): array
    {
        return [
            'number' => 'integer',
            'episode_count' => 'integer',
            'rating' => 'float',
        ];
    }
}
