<?php

namespace App\Http\Controllers;

use App\Enums\GoalPace;
use App\Support\GoalCensor;
use App\Support\PrototypeContent;
use Illuminate\View\View;

class GoalsController extends Controller
{
    /**
     * One page, three floors: daily chains, this year's goals and the long-term board.
     */
    public function __invoke(): View
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
}
