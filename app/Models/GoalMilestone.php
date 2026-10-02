<?php

namespace App\Models;

use App\Models\Concerns\Sortable;
use Carbon\CarbonImmutable;
use Database\Factories\GoalMilestoneFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * A milestone of a yearly goal; done when done_at is set.
 *
 * @property int $id
 * @property int $goal_id
 * @property string $title
 * @property CarbonImmutable|null $done_at
 * @property int $sort_order
 */
#[Fillable(['title', 'done_at', 'sort_order'])]
class GoalMilestone extends Model
{
    /** @use HasFactory<GoalMilestoneFactory> */
    use HasFactory, Sortable;

    /**
     * @param  Builder<static>  $query
     * @return Builder<static>
     */
    protected function sortSiblings(Builder $query): Builder
    {
        return $query->where('goal_id', $this->goal_id);
    }

    /**
     * @return BelongsTo<Goal, $this>
     */
    public function goal(): BelongsTo
    {
        return $this->belongsTo(Goal::class);
    }

    protected function casts(): array
    {
        return ['done_at' => 'immutable_datetime'];
    }
}
