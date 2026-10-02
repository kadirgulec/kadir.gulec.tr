<?php

namespace App\Models;

use Carbon\CarbonImmutable;
use Database\Factories\GoalProgressFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * One step of a numeric goal ("+1 book: Tutunamayanlar").
 *
 * @property int $id
 * @property int $goal_id
 * @property CarbonImmutable $date
 * @property int $amount
 * @property string|null $note
 */
#[Fillable(['date', 'amount', 'note'])]
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

    protected function casts(): array
    {
        return ['date' => 'immutable_date', 'amount' => 'integer'];
    }
}
