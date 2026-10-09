<?php

namespace App\Support\Content;

use App\Enums\ChainPeriod;
use App\Enums\GoalKind;
use App\Enums\GoalPace;
use App\Enums\GoalVisibility;
use App\Enums\Permission;
use App\Enums\ReviewItemKind;
use App\Models\Goal;
use App\Models\MonthlyReview;
use App\Models\Post;
use App\Models\ReviewItem;
use App\Support\GoalCensor;
use App\Support\TurkishDate;
use Carbon\CarbonImmutable;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\HtmlString;

/**
 * The monthly reviews as the goals section shows them. Numbers come from the
 * review's frozen stats; goal titles are looked up now, so a goal censored or
 * hidden since the review keeps its secret (hidden ones lose their tile).
 *
 * @phpstan-type Tile array{key: string, value: string, unit: ?string, label: ?string, labelLength: ?int, note: ?string, url: ?string}
 */
class ReviewContent
{
    /** How many months the tabs above a review show. */
    private const TAB_COUNT = 4;

    public function __construct(private GoalContent $goals) {}

    public function latestPublished(): ?MonthlyReview
    {
        return MonthlyReview::query()->published()->orderByDesc('month')->first();
    }

    /**
     * The review of a month ("2026-10"). Drafts only for those who manage goals.
     */
    public function find(string $monthKey): ?MonthlyReview
    {
        $month = CarbonImmutable::createFromFormat('!Y-m', $monthKey);

        if ($month === null || $month->format('Y-m') !== $monthKey) {
            return null;
        }

        $review = MonthlyReview::query()->whereDate('month', $month->toDateString())->with('items')->first();

        if ($review === null || (! $review->isPublished() && ! $this->canSeeDrafts())) {
            return null;
        }

        return $review;
    }

    /**
     * The small card on the goals page: the latest published review.
     *
     * @return array{monthName: string, score: ?int, summary: ?string, url: string}|null
     */
    public function card(): ?array
    {
        $review = $this->latestPublished();

        return $review === null ? null : [
            'monthName' => TurkishDate::monthYear($review->month),
            'score' => $review->score,
            'summary' => $review->summary,
            'url' => route('goals.reviews.show', $review->monthKey()),
        ];
    }

    /**
     * @return array<string, mixed>
     */
    public function toArray(MonthlyReview $review): array
    {
        $previous = $review->previousReview();
        $showPrevious = $previous !== null && ($previous->isPublished() || $this->canSeeDrafts());
        $published = MonthlyReview::query()->published()->orderByDesc('month')->get(['id', 'month', 'score', 'published_at']);

        return [
            'monthKey' => $review->monthKey(),
            'monthName' => TurkishDate::monthYear($review->month),
            'summary' => $review->summary,
            'score' => $review->score,
            'isDraft' => ! $review->isPublished(),
            'nextMonthIn' => TurkishDate::inMonth($review->month->addMonth()),
            'monthIn' => TurkishDate::inMonth($review->month),
            'tiles' => $this->tiles($review),
            'topPost' => in_array('topPost', $review->hidden_stats ?? [], true) ? null : $this->topPost($review),
            'items' => [
                ReviewItemKind::Good->value => $this->items($review->items, ReviewItemKind::Good),
                ReviewItemKind::Hard->value => $this->items($review->items, ReviewItemKind::Hard),
                ReviewItemKind::Try->value => $this->items($review->items, ReviewItemKind::Try),
            ],
            'previousTries' => $showPrevious ? $this->items($previous->items()->get(), ReviewItemKind::Try) : [],
            'tabs' => $this->tabs($review, $published),
            'newer' => $this->link($published->filter(fn (MonthlyReview $other): bool => $other->month->greaterThan($review->month))->last()),
            'older' => $this->link($published->first(fn (MonthlyReview $other): bool => $other->month->lessThan($review->month))),
            'archive' => $published->groupBy(fn (MonthlyReview $other): int => $other->month->year)
                ->map(fn (Collection $year): array => array_values($year->map(fn (MonthlyReview $other): array => [
                    'label' => TurkishDate::month($other->month),
                    'score' => $other->score,
                    'url' => route('goals.reviews.show', $other->monthKey()),
                    'current' => $other->is($review),
                ])->all()))
                ->all(),
            'nextReviewOn' => TurkishDate::dayMonth(CarbonImmutable::today()->endOfMonth()),
        ];
    }

    /**
     * The number tiles, in the order chains, goals, writing, visitors.
     *
     * @return list<Tile>
     */
    private function tiles(MonthlyReview $review): array
    {
        $stats = $review->stats;
        $hidden = $review->hidden_stats ?? [];
        $goals = Goal::query()->visible()
            ->whereKey([...array_column($stats['chains'], 'goal_id'), ...array_column($stats['yearly'], 'goal_id')])
            ->get()
            ->keyBy('id');
        $tiles = [];

        foreach ($stats['chains'] as $chain) {
            $goal = $goals->get($chain['goal_id']);

            if ($goal === null || $goal->kind !== GoalKind::Chain) {
                continue;
            }

            $period = ChainPeriod::from($chain['period']);
            $note = $period === ChainPeriod::Day
                ? 'başarı %'.$chain['successRate']
                : $chain['held'].' / '.$chain['links'].' '.$period->unit().' tuttu';

            if ($chain['record'] !== null) {
                $note .= ' · 🔥 yeni rekor: '.$chain['record'].' '.$period->unit();
            }

            $tiles[] = [
                'key' => 'chain:'.$goal->id,
                'value' => (string) $chain['doneDays'],
                'unit' => 'gün',
                ...$this->goalLabel($goal),
                'note' => $note,
                'url' => route('goals.chain', $goal->slugFor(auth()->user())),
            ];
        }

        foreach ($stats['yearly'] as $yearly) {
            $goal = $goals->get($yearly['goal_id']);

            if ($goal === null || $goal->kind !== GoalKind::Yearly) {
                continue;
            }

            $tiles[] = [
                'key' => 'yearly:'.$goal->id,
                'value' => '+'.$yearly['added'],
                'unit' => $yearly['unit'],
                ...$this->goalLabel($goal),
                'note' => trim($yearly['current'].' / '.$yearly['target'].' '.$yearly['unit']).' · '.GoalPace::from($yearly['pace'])->label(),
                'url' => route('goals.index').'#hedef-'.$goal->slugFor(auth()->user()),
            ];
        }

        foreach ([
            ['posts', $stats['published']['posts'], 'yazı', route('posts.index')],
            ['notes', $stats['published']['notes'], 'not', route('notes.index')],
            ['viewings', $stats['published']['viewings'], 'film / dizi', route('watched.index')],
            ['views', $stats['visitors']['views'], 'sayfa görüntüleme', null],
            ['visits', $stats['visitors']['visits'], 'ziyaret', null],
        ] as [$key, $value, $unit, $url]) {
            $tiles[] = ['key' => $key, 'value' => number_format($value, 0, ',', '.'), 'unit' => $unit, 'label' => null, 'labelLength' => null, 'note' => null, 'url' => $url];
        }

        return array_values(array_filter($tiles, fn (array $tile): bool => ! in_array($tile['key'], $hidden, true)));
    }

    /**
     * @return array{title: string, url: string, views: int}|null
     */
    private function topPost(MonthlyReview $review): ?array
    {
        $top = $review->stats['visitors']['topPost'];
        $post = $top !== null ? Post::query()->published()->whereKey($top['post_id'])->first(['id', 'title', 'slug', 'published_at']) : null;

        return $post === null ? null : ['title' => $post->title, 'url' => route('posts.show', $post->slug), 'views' => $top['views']];
    }

    /**
     * A goal's title as this viewer may read it, with the length a censored one blacks out.
     *
     * @return array{label: ?string, labelLength: ?int}
     */
    private function goalLabel(Goal $goal): array
    {
        if ($goal->visibility === GoalVisibility::Censored && $this->goals->canSeeCensored()) {
            return ['label' => $goal->title, 'labelLength' => null];
        }

        $censored = GoalCensor::apply(['visibility' => $goal->visibility, 'title' => $goal->title]);

        return ['label' => $censored['title'], 'labelLength' => $censored['titleLength']];
    }

    /**
     * @param  Collection<int, ReviewItem>  $items
     * @return list<array{html: HtmlString, outcome: ?string}>
     */
    private function items(Collection $items, ReviewItemKind $kind): array
    {
        return array_values($items->where('kind', $kind)->map(fn (ReviewItem $item): array => [
            'html' => new HtmlString((string) $item->body_html),
            'outcome' => $item->outcome?->value,
        ])->all());
    }

    /**
     * The latest months as tabs, with the current one among them even when it is older or a draft.
     *
     * @param  Collection<int, MonthlyReview>  $published
     * @return list<array{label: string, url: string, current: bool}>
     */
    private function tabs(MonthlyReview $review, Collection $published): array
    {
        $months = $published->take(self::TAB_COUNT);

        if (! $months->contains(fn (MonthlyReview $other): bool => $other->is($review))) {
            $months = $months->push($review)->sortByDesc(fn (MonthlyReview $other): string => $other->month->toDateString());
        }

        return array_values($months->map(fn (MonthlyReview $other): array => [
            'label' => TurkishDate::month($other->month).($other->month->year !== CarbonImmutable::today()->year ? ' '.$other->month->year : ''),
            'url' => route('goals.reviews.show', $other->monthKey()),
            'current' => $other->is($review),
        ])->all());
    }

    /**
     * @return array{label: string, url: string}|null
     */
    private function link(?MonthlyReview $review): ?array
    {
        return $review === null ? null : [
            'label' => TurkishDate::monthYear($review->month),
            'url' => route('goals.reviews.show', $review->monthKey()),
        ];
    }

    private function canSeeDrafts(): bool
    {
        return Gate::allows(Permission::ManageGoals->value);
    }
}
