<?php

namespace App\Models;

use App\Enums\ChainDayState;
use Carbon\CarbonImmutable;
use Database\Factories\ChainDayFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * A marked day of a chain: done or excused (with an optional private note).
 *
 * @property int $id
 * @property int $goal_id
 * @property CarbonImmutable $date
 * @property ChainDayState $state
 * @property string|null $note
 */
#[Fillable(['date', 'state', 'note'])]
class ChainDay extends Model
{
    /** @use HasFactory<ChainDayFactory> */
    use HasFactory;

    /**
     * @return BelongsTo<Goal, $this>
     */
    public function goal(): BelongsTo
    {
        return $this->belongsTo(Goal::class);
    }

    protected function casts(): array
    {
        return ['date' => 'immutable_date', 'state' => ChainDayState::class];
    }
}
