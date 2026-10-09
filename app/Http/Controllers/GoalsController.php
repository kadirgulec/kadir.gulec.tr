<?php

namespace App\Http\Controllers;

use App\Enums\GoalMeasure;
use App\Enums\GoalPace;
use App\Support\ChainStats;
use App\Support\Content\GoalContent;
use App\Support\Content\ReviewContent;
use Illuminate\View\View;

class GoalsController extends Controller
{
    public function __construct(private GoalContent $goals) {}

    /**
     * One page, three floors: daily chains, this year's goals and the long-term board.
     */
    public function index(ReviewContent $reviews): View
    {
        $today = now()->toImmutable();

        return view('site.goals.index', [
            'today' => $today,
            'yearShare' => GoalPace::yearShare($today),
            'chains' => $this->goals->chains(),
            'yearlyGoals' => array_map(fn (array $goal): array => [
                ...$goal,
                'pace' => $goal['type'] === GoalMeasure::Numeric->value && $goal['target'] ? GoalPace::evaluate($goal['current'], $goal['target'], $today) : null,
            ], $this->goals->yearlyGoals()),
            'longTermGoals' => $this->goals->longTermGoals(),
            'pastYearGoals' => $this->goals->pastYearGoals(),
            'reviewCard' => $reviews->card(),
        ]);
    }

    /**
     * A chain's whole year as a grid, with its numbers. Censored chains keep their title hidden.
     */
    public function chain(string $slug): View
    {
        $found = $this->goals->findChain($slug);

        abort_if($found === null, 404);

        // The grid shows this year's days; the counts are this year's links, the streaks the whole history.
        $links = array_column($found['links'], 'state');

        return view('site.goals.chain', [
            'chain' => $found['chain'],
            'history' => $found['history'],
            'parentGoal' => $found['parentGoal'],
            'stats' => [
                'streak' => $found['chain']['streak'],
                'bestStreak' => $found['chain']['bestStreak'],
                'done' => ChainStats::count($links, 'done'),
                'excused' => ChainStats::count($links, 'excused'),
                'successRate' => ChainStats::successRate($links),
            ],
        ]);
    }

    /**
     * A long-term goal: its reason, its story and the smaller goals that serve it.
     * Censored long-term goals have no page for viewers who cannot read them.
     */
    public function show(string $slug): View
    {
        $found = $this->goals->findLongTerm($slug);

        abort_if($found === null, 404);

        return view('site.goals.show', [
            'goal' => $found['goal'],
            'yearShare' => GoalPace::yearShare(now()->toImmutable()),
            'yearlyGoals' => $found['yearlyGoals'],
            'chains' => $found['chains'],
        ]);
    }
}
