<?php

namespace App\Models;

use App\Enums\ReviewItemKind;
use App\Enums\ReviewItemOutcome;
use App\Models\Concerns\RendersMarkdown;
use App\Models\Concerns\Sortable;
use Carbon\CarbonImmutable;
use Database\Factories\ReviewItemFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * One line of a monthly review: something that went well, something hard,
 * or something to try next month (whose outcome the next review records).
 *
 * @property int $id
 * @property int $monthly_review_id
 * @property ReviewItemKind $kind
 * @property string $body
 * @property string|null $body_html
 * @property ReviewItemOutcome|null $outcome
 * @property int $sort_order
 * @property CarbonImmutable|null $created_at
 * @property CarbonImmutable|null $updated_at
 */
#[Fillable(['kind', 'body', 'outcome', 'sort_order'])]
class ReviewItem extends Model
{
    /** @use HasFactory<ReviewItemFactory> */
    use HasFactory, RendersMarkdown, Sortable;

    /**
     * @param  Builder<static>  $query
     * @return Builder<static>
     */
    protected function sortSiblings(Builder $query): Builder
    {
        return $query->where('monthly_review_id', $this->monthly_review_id)->where('kind', $this->kind);
    }

    /**
     * @return BelongsTo<MonthlyReview, $this>
     */
    public function review(): BelongsTo
    {
        return $this->belongsTo(MonthlyReview::class, 'monthly_review_id');
    }

    protected function markdownColumns(): array
    {
        return ['body' => 'body_html'];
    }

    protected function casts(): array
    {
        return ['kind' => ReviewItemKind::class, 'outcome' => ReviewItemOutcome::class];
    }
}
