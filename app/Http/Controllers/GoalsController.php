<?php

namespace App\Http\Controllers;

use App\Enums\GoalPace;
use App\Enums\GoalVisibility;
use App\Support\ChainStats;
use App\Support\GoalCensor;
use App\Support\PrototypeContent;
use Illuminate\View\View;

class GoalsController extends Controller
{
    /**
     * One page, three floors: daily chains, this year's goals and the long-term board.
     */
    public function index(): View
    {
        $today = now();
        $chains = array_map(GoalCensor::apply(...), PrototypeContent::chains());
        $yearlyGoals = array_map(GoalCensor::apply(...), PrototypeContent::yearlyGoals());
        $longTermGoals = array_map(GoalCensor::apply(...), PrototypeContent::longTermGoals());

        // Parent chips can only point at goals the visitor is allowed to see.
        $parents = collect([...$longTermGoals, ...$yearlyGoals])->mapWithKeys(fn (array $goal): array => [
            $goal['slug'] => [
                'title' => $goal['title'],
                'titleLength' => $goal['titleLength'],
                'anchor' => '#hedef-'.$goal['slug'],
            ],
        ]);

        $withParent = fn (array $goal): array => [...$goal, 'parentGoal' => $parents->get($goal['parent'])];

        $childCounts = collect([...$chains, ...$yearlyGoals])->countBy('parent');

        return view('site.goals.index', [
            'today' => $today,
            'yearShare' => GoalPace::yearShare($today),
            'chains' => array_map($withParent, $chains),
            'yearlyGoals' => array_map(fn (array $goal): array => [
                ...$withParent($goal),
                'pace' => $goal['type'] === 'numeric' ? GoalPace::evaluate($goal['current'], $goal['target'], $today) : null,
            ], $yearlyGoals),
            'longTermGoals' => array_map(fn (array $goal): array => [
                ...$goal,
                'childCount' => $childCounts->get($goal['slug'], 0),
            ], $longTermGoals),
            'pastYearGoals' => PrototypeContent::pastYearGoals(),
        ]);
    }

    /**
     * A chain's whole year as a grid, with its numbers. Censored chains keep their title hidden.
     */
    public function chain(string $slug): View
    {
        $found = PrototypeContent::findChain($slug);

        abort_if($found === null, 404);

        $chain = GoalCensor::apply($found['chain']);
        $days = array_column($found['history'], 'state');
        $parent = $this->visibleGoal($chain['parent']);

        return view('site.goals.chain', [
            'chain' => $chain,
            'history' => $found['history'],
            'parentGoal' => $parent,
            'stats' => [
                'streak' => ChainStats::currentStreak($days),
                'bestStreak' => ChainStats::bestStreak($days),
                'done' => ChainStats::count($days, 'done'),
                'excused' => ChainStats::count($days, 'excused'),
                'successRate' => ChainStats::successRate($days),
            ],
        ]);
    }

    /**
     * A long-term goal: its reason, its story and the smaller goals that serve it.
     * Censored long-term goals have no page; their story would give them away.
     */
    public function show(string $slug): View
    {
        $goal = array_find(PrototypeContent::longTermGoals(), fn (array $goal): bool => $goal['slug'] === $slug);

        abort_if($goal === null || $goal['visibility'] !== GoalVisibility::Public, 404);

        $chains = array_map(GoalCensor::apply(...), PrototypeContent::chains());
        $yearlyGoals = array_map(fn (array $yearly): array => [
            ...GoalCensor::apply($yearly),
            'chains' => array_values(array_filter($chains, fn (array $chain): bool => $chain['parent'] === $yearly['slug'])),
        ], array_values(array_filter(PrototypeContent::yearlyGoals(), fn (array $yearly): bool => $yearly['parent'] === $slug)));

        return view('site.goals.show', [
            'goal' => $goal,
            'yearShare' => GoalPace::yearShare(now()),
            'yearlyGoals' => $yearlyGoals,
            'chains' => array_values(array_filter($chains, fn (array $chain): bool => $chain['parent'] === $slug)),
        ]);
    }

    /**
     * A long-term or yearly goal shaped for the parent chip, if the visitor may see it.
     *
     * @return array{title: ?string, titleLength: ?int, anchor: string}|null
     */
    private function visibleGoal(?string $slug): ?array
    {
        $goal = array_find([...PrototypeContent::longTermGoals(), ...PrototypeContent::yearlyGoals()], fn (array $goal): bool => $goal['slug'] === $slug);

        if ($goal === null) {
            return null;
        }

        $visible = GoalCensor::apply($goal);

        return [
            'title' => $visible['title'],
            'titleLength' => $visible['titleLength'],
            'anchor' => route('goals.index').'#hedef-'.$goal['slug'],
        ];
    }
}
