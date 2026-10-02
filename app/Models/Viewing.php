<?php

namespace App\Models;

use Carbon\CarbonImmutable;
use Database\Factories\ViewingFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * One line of the watching diary: when and where a film was watched, or a
 * meaningful moment of a series ("started", "finished season 2").
 * A rewatch is not stored; it is any viewing with an older one before it.
 *
 * @property int $id
 * @property int $watchable_id
 * @property CarbonImmutable $watched_on
 * @property string|null $place
 * @property string|null $note
 * @property CarbonImmutable|null $created_at
 * @property CarbonImmutable|null $updated_at
 */
#[Fillable(['watched_on', 'place', 'note'])]
class Viewing extends Model
{
    /** @use HasFactory<ViewingFactory> */
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
            'watched_on' => 'immutable_date',
        ];
    }
}
