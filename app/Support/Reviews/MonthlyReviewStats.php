<?php

namespace App\Support\Reviews;

use App\Enums\ChainDayState;
use App\Enums\GoalKind;
use App\Enums\GoalMeasure;
use App\Enums\GoalPace;
use App\Models\ChainDay;
use App\Models\Goal;
use App\Models\GoalProgress;
use App\Models\Note;
use App\Models\PageView;
use App\Models\Post;
use App\Models\Viewing;
use App\Support\ChainStats;
use Carbon\CarbonImmutable;
use Illuminate\Database\Eloquent\Builder;

/**
 * The numbers of one month, taken from the site's records: chains, numeric
 * yearly goals, what was published and how many people came by. Goals are
 * kept by id, never by title, so a review shows each goal as the viewer may
 * see it today (censored or hidden goals stay so). Hidden goals are left out.
 *
 * @phpstan-type ChainNumbers array{goal_id: int, period: string, target: int, doneDays: int, excusedDays: int, links: int, held: int, excused: int, successRate: int, bestStreak: int, record: int|null}
 * @phpstan-type YearlyNumbers array{goal_id: int, unit: string|null, target: int, added: int, current: int, pace: string}
 * @phpstan-type Stats array{chains: list<ChainNumbers>, yearly: list<YearlyNumbers>, published: array{posts: int, notes: int, viewings: int}, visitors: array{views: int, visits: int, topPost: array{post_id: int, views: int}|null}}
 */
class MonthlyReviewStats
{
    /**
     * @return Stats
     */
    public function for(CarbonImmutable $month): array
    {
        $start = $month->startOfMonth();
        $end = $month->endOfMonth();

        return [
            'chains' => $this->chains($start, $end),
            'yearly' => $this->yearly($start, $end),
            'published' => $this->published($start, $end),
            'visitors' => $this->visitors($start, $end),
        ];
    }

    /**
     * Every visible chain that ran during the month. A weekly or monthly link
     * belongs to the month its period ends in. A new record counts only after
     * a real one (at least ChainPeriod::recordMinimum() links), as in the
     * follower notifications.
     *
     * @return list<ChainNumbers>
     */
    private function chains(CarbonImmutable $start, CarbonImmutable $end): array
    {
        $chains = Goal::query()->ofKind(GoalKind::Chain)->visible()
            ->where(fn (Builder $query) => $query->whereNull('started_on')->orWhere('started_on', '<=', $end->toDateString()))
            ->where(fn (Builder $query) => $query->whereNull('ended_on')->orWhere('ended_on', '>=', $start->toDateString()))
            ->with('chainDays')
            ->orderBy('sort_order')
            ->orderBy('id')
            ->get();

        $numbers = [];

        foreach ($chains as $chain) {
            $links = $chain->chainLinks();
            $before = array_column(array_filter($links, fn (array $link): bool => $link['end']->lessThan($start)), 'state');
            $untilEnd = array_column(array_filter($links, fn (array $link): bool => $link['end']->lessThanOrEqualTo($end)), 'state');
            $inMonth = array_column(array_filter($links, fn (array $link): bool => $link['end']->betweenIncluded($start, $end)), 'state');
            $days = $chain->chainDays->filter(fn (ChainDay $day): bool => $day->date->betweenIncluded($start, $end));

            if ($inMonth === [] && $days->isEmpty()) {
                continue;
            }

            $bestBefore = ChainStats::bestStreak($before);
            $bestUntilEnd = ChainStats::bestStreak($untilEnd);

            $numbers[] = [
                'goal_id' => $chain->id,
                'period' => $chain->chain_period->value,
                'target' => $chain->chain_target,
                'doneDays' => $days->where('state', ChainDayState::Done)->count(),
                'excusedDays' => $days->where('state', ChainDayState::Excused)->count(),
                'links' => count($inMonth),
                'held' => ChainStats::count($inMonth, 'done'),
                'excused' => ChainStats::count($inMonth, 'excused'),
                'successRate' => ChainStats::successRate($inMonth),
                'bestStreak' => ChainStats::bestStreak($inMonth),
                'record' => $bestUntilEnd > $bestBefore && $bestBefore >= $chain->chain_period->recordMinimum() ? $bestUntilEnd : null,
            ];
        }

        return $numbers;
    }

    /**
     * The visible numeric goals of the month's year: what the month added and
     * where the goal stood at its end.
     *
     * @return list<YearlyNumbers>
     */
    private function yearly(CarbonImmutable $start, CarbonImmutable $end): array
    {
        $goals = Goal::query()->ofKind(GoalKind::Yearly)->visible()
            ->where('year', $start->year)
            ->where('measure', GoalMeasure::Numeric)
            ->where('target', '>', 0)
            ->with('progressEntries')
            ->orderBy('sort_order')
            ->orderBy('id')
            ->get();

        return array_values($goals->map(function (Goal $goal) use ($start, $end): array {
            $current = (int) $goal->progressEntries->filter(fn (GoalProgress $entry): bool => $entry->date->lessThanOrEqualTo($end))->sum('amount');

            return [
                'goal_id' => $goal->id,
                'unit' => $goal->unit,
                'target' => (int) $goal->target,
                'added' => (int) $goal->progressEntries->filter(fn (GoalProgress $entry): bool => $entry->date->betweenIncluded($start, $end))->sum('amount'),
                'current' => $current,
                'pace' => GoalPace::evaluate($current, (int) $goal->target, $end)->value,
            ];
        })->all());
    }

    /**
     * @return array{posts: int, notes: int, viewings: int}
     */
    private function published(CarbonImmutable $start, CarbonImmutable $end): array
    {
        return [
            'posts' => Post::query()->published()->whereBetween('published_at', [$start, $end])->count(),
            'notes' => Note::query()->published()->whereBetween('published_at', [$start, $end])->count(),
            'viewings' => Viewing::query()
                ->whereBetween('watched_on', [$start->toDateString(), $end->toDateString()])
                ->whereHas('watchable', fn (Builder $query) => $query->published())
                ->count(),
        ];
    }

    /**
     * Page views and visits (distinct daily visitor hashes, as on the admin's
     * visitors page), and the most read published post.
     *
     * @return array{views: int, visits: int, topPost: array{post_id: int, views: int}|null}
     */
    private function visitors(CarbonImmutable $start, CarbonImmutable $end): array
    {
        $inMonth = fn () => PageView::query()->whereBetween('created_at', [$start, $end]);
        $totals = $inMonth()->selectRaw('COUNT(*) as views, COUNT(DISTINCT visitor_hash) as visits')->toBase()->first();

        $postViews = $inMonth()
            ->where('path', 'like', '/yazilar/%')
            ->selectRaw('path, COUNT(*) as views')
            ->groupBy('path')
            ->orderByDesc('views')
            ->orderBy('path')
            ->limit(20)
            ->toBase()
            ->get();

        $posts = Post::query()->published()
            ->whereIn('slug', $postViews->map(fn (object $row): string => substr((string) $row->path, strlen('/yazilar/'))))
            ->pluck('id', 'slug');

        $top = $postViews->first(fn (object $row): bool => $posts->has(substr((string) $row->path, strlen('/yazilar/'))));

        return [
            'views' => (int) ($totals->views ?? 0),
            'visits' => (int) ($totals->visits ?? 0),
            'topPost' => $top !== null ? ['post_id' => (int) $posts[substr((string) $top->path, strlen('/yazilar/'))], 'views' => (int) $top->views] : null,
        ];
    }
}
