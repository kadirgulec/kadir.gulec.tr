<?php

namespace App\Support\Content;

use App\Enums\GoalKind;
use App\Enums\GoalMeasure;
use App\Enums\GoalVisibility;
use App\Enums\Permission;
use App\Models\DevlogEntry;
use App\Models\Goal;
use App\Models\GoalMilestone;
use App\Models\GoalProgress;
use App\Models\Project;
use App\Support\ChainStats;
use App\Support\GoalCensor;
use Carbon\CarbonImmutable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\HtmlString;

/**
 * Goals in the shape the site views expect, with the visibility rules
 * applied in one place:
 *
 *  - hidden goals never leave the database for the site,
 *  - censored goals keep their numbers and shapes, but their words (title,
 *    reason, updates, milestone names, progress notes) are only sent to
 *    viewers with the goals.view-censored permission (close friends, Kadir),
 *  - for everyone else a censored goal's address is opaque ("k-12"),
 *    because its slug is made from the very title being hidden.
 */
class GoalContent
{
    /**
     * Chains that run today, in Kadir's order, with their last days.
     *
     * @return list<array<string, mixed>>
     */
    public function chains(int $days = 21): array
    {
        $chains = $this->visible(GoalKind::Chain)->activeChains()->with(['chainDays', 'parent'])->get();

        return array_values($chains->map(fn (Goal $chain): array => $this->chain($chain, $days))->all());
    }

    /**
     * A visible chain with its days since the start of the year (or its own start).
     *
     * @return array{chain: array<string, mixed>, history: list<array{date: CarbonImmutable, state: 'done'|'missed'|'excused'}>, parentGoal: array{title: ?string, titleLength: ?int, anchor: string}|null}|null
     */
    public function findChain(string $slug): ?array
    {
        $chain = $this->findBySlug($this->visible(GoalKind::Chain)->with(['chainDays', 'parent']), $slug);

        if ($chain === null) {
            return null;
        }

        return [
            'chain' => [...$this->chain($chain, 21), 'allStates' => array_column($chain->chainHistory(), 'state')],
            'history' => $chain->chainHistory(CarbonImmutable::today()->startOfYear()),
            'parentGoal' => $this->chip($chain->parent, onGoalsPage: false),
        ];
    }

    /**
     * The yearly goals of a year (this year by default).
     *
     * @return list<array<string, mixed>>
     */
    public function yearlyGoals(?int $year = null): array
    {
        $goals = $this->visible(GoalKind::Yearly)
            ->where('year', $year ?? CarbonImmutable::today()->year)
            ->with(['milestones', 'progressEntries', 'parent', 'projects' => fn ($query) => $query->published()])
            ->get();

        return array_values($goals->map($this->yearly(...))->all());
    }

    /**
     * Past years, newest first, with whether each goal was reached.
     *
     * @return array<int, list<array{title: ?string, titleLength: ?int, achieved: bool}>>
     */
    public function pastYearGoals(): array
    {
        $goals = $this->visible(GoalKind::Yearly)
            ->where('year', '<', CarbonImmutable::today()->year)
            ->with(['milestones', 'progressEntries'])
            ->reorder()
            ->orderByDesc('year')
            ->orderBy('sort_order')
            ->orderBy('id')
            ->get();

        return $goals->groupBy('year')->map(fn (Collection $yearGoals): array => array_values($yearGoals->map(function (Goal $goal): array {
            $censored = $this->censor(['visibility' => $goal->visibility, 'title' => $goal->title]);

            return ['title' => $censored['title'], 'titleLength' => $censored['titleLength'], 'achieved' => $goal->isAchieved()];
        })->all()))->all();
    }

    /**
     * @return list<array<string, mixed>>
     */
    public function longTermGoals(): array
    {
        $goals = $this->visible(GoalKind::LongTerm)->with('updates')->withCount([
            'children' => fn (Builder $query) => $query->where('visibility', '!=', GoalVisibility::Hidden),
        ])->get();

        return array_values($goals->map($this->longTerm(...))->all());
    }

    /**
     * A long-term goal page with its children. Censored goals have no page
     * for viewers who cannot read them: the story would give them away.
     *
     * @return array{goal: array<string, mixed>, yearlyGoals: list<array<string, mixed>>, chains: list<array<string, mixed>>}|null
     */
    public function findLongTerm(string $slug): ?array
    {
        $goal = $this->findBySlug($this->visible(GoalKind::LongTerm)->with('updates')->withCount('children'), $slug);

        if ($goal === null || ($goal->visibility === GoalVisibility::Censored && ! $this->canSeeCensored())) {
            return null;
        }

        $children = $goal->children()->where('visibility', '!=', GoalVisibility::Hidden)
            ->with(['milestones', 'progressEntries', 'chainDays', 'parent', 'projects' => fn ($query) => $query->published()])
            ->get();

        return [
            'goal' => $this->longTerm($goal),
            'yearlyGoals' => array_values($children->filter(fn (Goal $child): bool => $child->kind === GoalKind::Yearly && $child->year === CarbonImmutable::today()->year)->map($this->yearly(...))->all()),
            'chains' => array_values($children->filter(fn (Goal $child): bool => $child->kind === GoalKind::Chain && $child->ended_on === null)->map(fn (Goal $chain): array => $this->chain($chain, 21))->all()),
        ];
    }

    /**
     * The chip of a parent goal ("↑ Kendi ürünüm"), or null when the viewer may not know it.
     *
     * @return array{title: ?string, titleLength: ?int, anchor: string}|null
     */
    public function chip(?Goal $goal, bool $onGoalsPage = true): ?array
    {
        if ($goal === null || $goal->visibility === GoalVisibility::Hidden) {
            return null;
        }

        $censored = $this->censor(['visibility' => $goal->visibility, 'title' => $goal->title]);
        $anchor = '#hedef-'.$this->slugFor($goal);

        return [
            'title' => $censored['title'],
            'titleLength' => $censored['titleLength'],
            'anchor' => $onGoalsPage ? $anchor : route('goals.index').$anchor,
        ];
    }

    /**
     * The first public chain, for the "now" list on the about page.
     *
     * @return array<string, mixed>|null
     */
    public function firstPublicChain(): ?array
    {
        return array_find($this->chains(), fn (array $chain): bool => $chain['visibility'] === GoalVisibility::Public);
    }

    /**
     * This year's public reading goal (a numeric goal counted in "kitap").
     *
     * @return array<string, mixed>|null
     */
    public function readingGoal(): ?array
    {
        return array_find($this->yearlyGoals(), fn (array $goal): bool => $goal['visibility'] === GoalVisibility::Public
            && $goal['type'] === GoalMeasure::Numeric->value
            && mb_strtolower((string) $goal['unit']) === 'kitap');
    }

    /**
     * The address part of a goal as this viewer may see it.
     */
    public function slugFor(Goal $goal): string
    {
        return $goal->visibility === GoalVisibility::Censored && ! $this->canSeeCensored() ? 'k-'.$goal->id : $goal->slug;
    }

    /**
     * Finds a goal by the address part this viewer would have been given.
     *
     * @param  Builder<Goal>  $query
     */
    private function findBySlug(Builder $query, string $slug): ?Goal
    {
        if (preg_match('/^k-(\d+)$/', $slug, $match)) {
            $goal = $query->whereKey((int) $match[1])->first();

            return $goal?->visibility === GoalVisibility::Censored ? $goal : null;
        }

        $goal = $query->where('slug', $slug)->first();

        return $goal !== null && $this->slugFor($goal) === $slug ? $goal : null;
    }

    /**
     * Asked every time (not memoized): the instance may outlive one request,
     * e.g. inside a controller cached on its route.
     */
    public function canSeeCensored(): bool
    {
        return Gate::allows(Permission::ViewCensoredGoals->value);
    }

    /**
     * @return Builder<Goal>
     */
    private function visible(GoalKind $kind): Builder
    {
        return Goal::query()->ofKind($kind)->visible()->orderBy('sort_order')->orderBy('id');
    }

    /**
     * @param  array{visibility: GoalVisibility, title: string, ...}  $goal
     * @return array<string, mixed>
     */
    private function censor(array $goal): array
    {
        if ($goal['visibility'] === GoalVisibility::Censored && $this->canSeeCensored()) {
            return [...$goal, 'titleLength' => null, 'whyLength' => null];
        }

        return GoalCensor::apply($goal);
    }

    /**
     * @return array<string, mixed>
     */
    private function chain(Goal $chain, int $days): array
    {
        $history = array_column($chain->chainHistory(), 'state');

        return [
            ...$this->censor([
                'id' => $chain->id,
                'slug' => $this->slugFor($chain),
                'title' => $chain->title,
                'visibility' => $chain->visibility,
                'parent' => $chain->parent ? $this->slugFor($chain->parent) : null,
                'days' => array_slice($history, -$days),
                'streak' => ChainStats::currentStreak($history),
                'bestStreak' => ChainStats::bestStreak($history),
            ]),
            'parentGoal' => $this->chip($chain->parent),
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function yearly(Goal $goal): array
    {
        $project = $goal->projects->first();

        return [
            ...$this->censor([
                'id' => $goal->id,
                'slug' => $this->slugFor($goal),
                'title' => $goal->title,
                'type' => $goal->measure->value ?? GoalMeasure::Binary->value,
                'visibility' => $goal->visibility,
                'current' => $goal->measure === GoalMeasure::Numeric ? $goal->current() : null,
                'target' => $goal->target,
                'unit' => $goal->unit,
                'milestones' => array_values($goal->milestones->map(fn (GoalMilestone $milestone): array => ['title' => $milestone->title, 'done' => $milestone->done_at !== null])->all()),
                'achievedAt' => $goal->measure === GoalMeasure::Binary ? $goal->achieved_at : ($goal->isAchieved() ? ($goal->achieved_at ?? $goal->updated_at) : null),
                'parent' => $goal->parent ? $this->slugFor($goal->parent) : null,
                'linkUrl' => $project instanceof Project ? route('projects.show', $project->slug) : null,
                'progressNotes' => $goal->show_progress_notes
                    ? array_values($goal->progressEntries->filter(fn (GoalProgress $entry): bool => filled($entry->note))->map(fn (GoalProgress $entry): array => ['date' => $entry->date, 'note' => (string) $entry->note])->all())
                    : [],
            ]),
            'parentGoal' => $this->chip($goal->parent),
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function longTerm(Goal $goal): array
    {
        return [
            ...$this->censor([
                'id' => $goal->id,
                'slug' => $this->slugFor($goal),
                'title' => $goal->title,
                'why' => filled($goal->why_html) ? new HtmlString((string) $goal->why_html) : null,
                'visibility' => $goal->visibility,
                'since' => $goal->started_year,
                'imageUrl' => $goal->imageUrl(960),
                'updates' => array_values($goal->updates->map(fn (DevlogEntry $entry): array => [
                    'date' => $entry->date,
                    'html' => new HtmlString((string) $entry->body_html),
                    'text' => trim(strip_tags((string) $entry->body_html)),
                ])->all()),
            ]),
            'childCount' => (int) ($goal->children_count ?? 0),
        ];
    }
}
