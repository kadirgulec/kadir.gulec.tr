<?php

namespace App\Models;

use Carbon\CarbonImmutable;
use Database\Factories\GoalProgressFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * One step of a numeric goal ("+1 book: Tutunamayanlar"). A step with a
 * reading was written by "Okuduklarım'dan sayılsın" and follows that reading.
 *
 * @property int $id
 * @property int $goal_id
 * @property int|null $reading_id
 * @property CarbonImmutable $date
 * @property int $amount
 * @property string|null $note
 */
#[Fillable(['date', 'amount', 'note', 'reading_id'])]
class GoalProgress extends Model
{
    /** @use HasFactory<GoalProgressFactory> */
    use HasFactory;

    protected $table = 'goal_progress';

    /**
     * @return BelongsTo<Goal, $this>
     */
    public function goal(): BelongsTo
    {
        return $this->belongsTo(Goal::class);
    }

    /**
     * @return BelongsTo<Reading, $this>
     */
    public function reading(): BelongsTo
    {
        return $this->belongsTo(Reading::class);
    }

    public function isFromReading(): bool
    {
        return $this->reading_id !== null;
    }

    protected function casts(): array
    {
        return ['date' => 'immutable_date', 'amount' => 'integer'];
    }
}
