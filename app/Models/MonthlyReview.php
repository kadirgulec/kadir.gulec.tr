<?php

namespace App\Models;

use App\Models\Concerns\HasPublication;
use App\Support\Reviews\MonthlyReviewStats;
use Carbon\CarbonImmutable;
use Database\Factories\MonthlyReviewFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * An honest look back at one month: numbers frozen from the site's records
 * when the review is made, and what went well, what was hard and what to try
 * next, written by Kadir.
 *
 * @property int $id
 * @property CarbonImmutable $month
 * @property string|null $summary
 * @property int|null $score
 * @property array<string, mixed> $stats
 * @property list<string>|null $hidden_stats
 * @property CarbonImmutable|null $announced_at
 * @property CarbonImmutable|null $created_at
 * @property CarbonImmutable|null $updated_at
 */
#[Fillable(['summary', 'score', 'hidden_stats', 'published_at'])]
class MonthlyReview extends Model
{
    /** @use HasFactory<MonthlyReviewFactory> */
    use HasFactory, HasPublication;

    /**
     * A new review for the month around $date, with its numbers worked out now.
     */
    public static function makeFor(CarbonImmutable $date): self
    {
        $review = new self;
        $review->month = $date->startOfMonth();
        $review->refreshStats();

        return $review;
    }

    /**
     * Works the month's numbers out again from the site's records (not saved).
     */
    public function refreshStats(): void
    {
        $this->stats = app(MonthlyReviewStats::class)->for($this->month);
    }

    /**
     * The month as it appears in the address: 2026-10.
     */
    public function monthKey(): string
    {
        return $this->month->format('Y-m');
    }

    public function publicPath(): string
    {
        return '/hedefler/aylik/'.$this->monthKey();
    }

    /**
     * The review of the month before, whose "try" items this one looks back on.
     */
    public function previousReview(): ?self
    {
        return self::query()->whereDate('month', $this->month->subMonth()->toDateString())->first();
    }

    /**
     * @return HasMany<ReviewItem, $this>
     */
    public function items(): HasMany
    {
        return $this->hasMany(ReviewItem::class)->orderBy('sort_order')->orderBy('id');
    }

    protected function casts(): array
    {
        return [
            'month' => 'immutable_date',
            'score' => 'integer',
            'stats' => 'array',
            'hidden_stats' => 'array',
            'announced_at' => 'immutable_datetime',
        ];
    }
}
