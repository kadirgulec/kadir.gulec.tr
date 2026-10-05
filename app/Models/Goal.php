<?php

namespace App\Models;

use App\Enums\ChainDayState;
use App\Enums\ChainPeriod;
use App\Enums\GoalKind;
use App\Enums\GoalMeasure;
use App\Enums\GoalVisibility;
use App\Models\Concerns\HasFollowers;
use App\Models\Concerns\HasSlugRedirects;
use App\Models\Concerns\RendersMarkdown;
use App\Models\Concerns\Sortable;
use App\Support\Images\ImageStore;
use Carbon\CarbonImmutable;
use Database\Factories\GoalFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Scope;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\MorphMany;

/**
 * A goal of any kind: a chain (daily, weekly or monthly), a yearly goal (numeric, milestones or
 * yes/no) or a long-term goal. Chains and yearly goals may serve one
 * long-term goal (parent_id); long-term goals have no parent.
 *
 * @property int $id
 * @property GoalKind $kind
 * @property string $title
 * @property string $slug
 * @property GoalVisibility $visibility
 * @property int|null $parent_id
 * @property int $sort_order
 * @property int|null $year
 * @property GoalMeasure|null $measure
 * @property int|null $target
 * @property string|null $unit
 * @property CarbonImmutable|null $achieved_at
 * @property bool $show_progress_notes
 * @property string|null $why
 * @property string|null $why_html
 * @property string|null $image_path
 * @property int|null $started_year
 * @property CarbonImmutable|null $started_on
 * @property CarbonImmutable|null $ended_on
 * @property ChainPeriod $chain_period
 * @property int $chain_target
 * @property CarbonImmutable|null $created_at
 * @property CarbonImmutable|null $updated_at
 */
#[Fillable([
    'kind', 'title', 'slug', 'visibility', 'parent_id', 'sort_order', 'year', 'measure', 'target', 'unit',
    'achieved_at', 'show_progress_notes', 'why', 'started_year', 'started_on', 'ended_on', 'chain_period', 'chain_target',
])]
class Goal extends Model
{
    /** @use HasFactory<GoalFactory> */
    use HasFactory, HasFollowers, HasSlugRedirects, RendersMarkdown, Sortable;

    /**
     * The database defaults, so a fresh model has them before it is reloaded.
     *
     * @var array<string, mixed>
     */
    protected $attributes = [
        'visibility' => 'hidden',
        'sort_order' => 0,
        'show_progress_notes' => false,
        'chain_period' => 'day',
        'chain_target' => 1,
    ];

    protected static function booted(): void
    {
        static::deleting(function (Goal $goal): void {
            $goal->updates()->delete();
            app(ImageStore::class)->delete($goal->image_path);
        });
    }

    /**
     * @param  Builder<static>  $query
     */
    #[Scope]
    protected function ofKind(Builder $query, GoalKind $kind): void
    {
        $query->where('kind', $kind);
    }

    /**
     * Goals any visitor may know exist (public and censored).
     *
     * @param  Builder<static>  $query
     */
    #[Scope]
    protected function visible(Builder $query): void
    {
        $query->where('visibility', '!=', GoalVisibility::Hidden);
    }

    /**
     * Chains that run today (started, not ended).
     *
     * @param  Builder<static>  $query
     */
    #[Scope]
    protected function activeChains(Builder $query): void
    {
        $today = today()->toDateString();

        $query->where('kind', GoalKind::Chain)
            ->where(fn (Builder $query) => $query->whereNull('started_on')->orWhere('started_on', '<=', $today))
            ->where(fn (Builder $query) => $query->whereNull('ended_on')->orWhere('ended_on', '>=', $today));
    }

    /**
     * @param  Builder<static>  $query
     * @return Builder<static>
     */
    protected function sortSiblings(Builder $query): Builder
    {
        return $query->where('kind', $this->kind)->when($this->kind === GoalKind::Yearly, fn (Builder $query) => $query->where('year', $this->year));
    }

    /**
     * @return BelongsTo<Goal, $this>
     */
    public function parent(): BelongsTo
    {
        return $this->belongsTo(Goal::class, 'parent_id');
    }

    /**
     * @return HasMany<Goal, $this>
     */
    public function children(): HasMany
    {
        return $this->hasMany(Goal::class, 'parent_id')->orderBy('sort_order')->orderBy('id');
    }

    /**
     * @return HasMany<GoalMilestone, $this>
     */
    public function milestones(): HasMany
    {
        return $this->hasMany(GoalMilestone::class)->orderBy('sort_order')->orderBy('id');
    }

    /**
     * @return HasMany<GoalProgress, $this>
     */
    public function progressEntries(): HasMany
    {
        return $this->hasMany(GoalProgress::class)->orderByDesc('date')->orderByDesc('id');
    }

    /**
     * @return HasMany<ChainDay, $this>
     */
    public function chainDays(): HasMany
    {
        return $this->hasMany(ChainDay::class)->orderBy('date');
    }

    /**
     * Dated updates of a long-term goal (the same logbook as a project's devlog).
     *
     * @return MorphMany<DevlogEntry, $this>
     */
    public function updates(): MorphMany
    {
        return $this->morphMany(DevlogEntry::class, 'loggable')->orderByDesc('date')->orderByDesc('id');
    }

    /**
     * @return HasMany<Project, $this>
     */
    public function projects(): HasMany
    {
        return $this->hasMany(Project::class);
    }

    /**
     * Progress of a numeric goal: the sum of its entries.
     */
    public function current(): int
    {
        if ($this->relationLoaded('progressEntries')) {
            return (int) $this->progressEntries->sum('amount');
        }

        return (int) $this->progressEntries()->sum('amount');
    }

    /**
     * Whether a yearly goal was reached, worked out from its measure.
     */
    public function isAchieved(): bool
    {
        return match ($this->measure) {
            GoalMeasure::Numeric => $this->target !== null && $this->target > 0 && $this->current() >= $this->target,
            GoalMeasure::Milestones => $this->milestones->isNotEmpty() && $this->milestones->every(fn (GoalMilestone $milestone): bool => $milestone->done_at !== null),
            GoalMeasure::Binary => $this->achieved_at !== null,
            null => false,
        };
    }

    /**
     * Every day of a chain from its start (or $from) to today, oldest first.
     * Days without a mark are missed, except today, which is left out until
     * it is marked: the day is not over yet.
     *
     * @return list<array{date: CarbonImmutable, state: 'done'|'missed'|'excused'}>
     */
    public function chainHistory(?CarbonImmutable $from = null): array
    {
        $today = CarbonImmutable::today();
        $end = $this->ended_on !== null && $this->ended_on->lessThan($today) ? $this->ended_on : $today;
        $start = $this->started_on ?? $this->chainDays->first()->date ?? $end;
        $start = $from !== null && $from->greaterThan($start) ? $from : $start;

        $marked = $this->chainDays->mapWithKeys(fn (ChainDay $day): array => [$day->date->toDateString() => $day->state]);
        $history = [];

        for ($date = $start; $date->lessThanOrEqualTo($end); $date = $date->addDay()) {
            $state = $marked->get($date->toDateString());

            if ($state === null && $date->isSameDay($today)) {
                break;
            }

            $history[] = ['date' => $date, 'state' => $state instanceof ChainDayState ? $state->value : 'missed'];
        }

        return $history;
    }

    /**
     * The links of a chain from its start (or the period around $from) to today,
     * oldest first. A daily chain has one link per day of chainHistory(). A weekly
     * or monthly link holds when chain_target of its days are done, and counts as
     * excused when excused days make up the rest. A period that is not over yet,
     * or that the chain only partly covers (started or ended halfway), is left out
     * until it holds: it can still be reached, or was never fully asked for.
     *
     * @return list<array{start: CarbonImmutable, end: CarbonImmutable, state: 'done'|'missed'|'excused', done: int, excused: int}>
     */
    public function chainLinks(?CarbonImmutable $from = null): array
    {
        if ($this->chain_period === ChainPeriod::Day) {
            return array_map(fn (array $day): array => [
                'start' => $day['date'],
                'end' => $day['date'],
                'state' => $day['state'],
                'done' => (int) ($day['state'] === 'done'),
                'excused' => (int) ($day['state'] === 'excused'),
            ], $this->chainHistory($from));
        }

        $today = CarbonImmutable::today();
        $end = $this->ended_on !== null && $this->ended_on->lessThan($today) ? $this->ended_on : $today;
        $start = $this->started_on ?? $this->chainDays->first()->date ?? $end;
        $first = $this->chain_period->start($from !== null && $from->greaterThan($start) ? $from : $start);
        $links = [];

        for ($periodStart = $first; $periodStart->lessThanOrEqualTo($end); $periodStart = $this->chain_period->next($periodStart)) {
            $link = $this->chainPeriodAt($periodStart);
            $whole = $link['start']->greaterThanOrEqualTo($start)
                && $link['end']->lessThan($today)
                && ($this->ended_on === null || $link['end']->lessThanOrEqualTo($this->ended_on));

            if ($link['state'] !== null || $whole) {
                $links[] = [...$link, 'state' => $link['state'] ?? 'missed'];
            }
        }

        return $links;
    }

    /**
     * Where the chain stands in the period around $date (today by default):
     * what is marked, what is still needed and how many days are left for it,
     * today included. A daily chain's period is the day itself.
     *
     * @return array{start: CarbonImmutable, end: CarbonImmutable, state: 'done'|'excused'|null, done: int, excused: int, target: int, needed: int, daysLeft: int, partial: bool}
     */
    public function chainPeriodAt(?CarbonImmutable $date = null): array
    {
        $date ??= CarbonImmutable::today();
        $start = $this->chain_period->start($date);
        $end = $this->chain_period->end($start);
        $last = $this->ended_on !== null && $this->ended_on->lessThan($end) ? $this->ended_on : $end;
        $target = max(1, $this->chain_target);
        $done = 0;
        $excused = 0;

        foreach ($this->chainDays as $day) {
            if ($day->date->betweenIncluded($start, $end)) {
                $day->state === ChainDayState::Done ? $done++ : $excused++;
            }
        }

        return [
            'start' => $start,
            'end' => $end,
            'state' => match (true) {
                $done >= $target => 'done',
                $done + $excused >= $target => 'excused',
                default => null,
            },
            'done' => $done,
            'excused' => $excused,
            'target' => $target,
            'needed' => max(0, $target - $done - $excused),
            'daysLeft' => max(0, (int) $date->startOfDay()->diffInDays($last) + 1),
            'partial' => ($this->started_on !== null && $this->started_on->greaterThan($start)) || $last->lessThan($end),
        ];
    }

    public function imageUrl(int $width = 960): ?string
    {
        return ImageStore::url($this->image_path, $width);
    }

    public function publicPath(?string $slug = null): string
    {
        return match ($this->kind) {
            GoalKind::Chain => '/hedefler/zincir/'.($slug ?? $this->slug),
            GoalKind::LongTerm => '/hedefler/'.($slug ?? $this->slug),
            GoalKind::Yearly => '/hedefler',
        };
    }

    /**
     * Only chains and long-term goals have their own address.
     */
    protected function wasPublishedBefore(): bool
    {
        return $this->kind !== GoalKind::Yearly && $this->getOriginal('visibility') === GoalVisibility::Public;
    }

    protected function slugSource(): string
    {
        return 'title';
    }

    protected function markdownColumns(): array
    {
        return ['why' => 'why_html'];
    }

    protected function casts(): array
    {
        return [
            'kind' => GoalKind::class,
            'visibility' => GoalVisibility::class,
            'measure' => GoalMeasure::class,
            'year' => 'integer',
            'target' => 'integer',
            'started_year' => 'integer',
            'show_progress_notes' => 'boolean',
            'achieved_at' => 'immutable_datetime',
            'started_on' => 'immutable_date',
            'ended_on' => 'immutable_date',
            'chain_period' => ChainPeriod::class,
            'chain_target' => 'integer',
        ];
    }
}
