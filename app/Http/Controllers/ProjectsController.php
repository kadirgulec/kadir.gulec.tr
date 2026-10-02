<?php

namespace App\Http\Controllers;

use App\Support\GoalCensor;
use App\Support\PrototypeContent;
use Illuminate\View\View;

class ProjectsController extends Controller
{
    /**
     * The featured project first, then the others grouped by their stamp.
     */
    public function index(): View
    {
        $projects = collect(PrototypeContent::projects());
        $featured = $projects->firstWhere('isFeatured', true);

        return view('site.projects.index', [
            'projectCount' => $projects->count(),
            'featured' => $featured,
            'projects' => $projects->reject(fn (array $project): bool => $project['slug'] === $featured['slug'])->values()->all(),
        ]);
    }

    /**
     * A case study with screenshots and the devlog; small projects show their summary only.
     */
    public function show(string $slug): View
    {
        $project = PrototypeContent::findProject($slug);

        abort_if($project === null, 404);

        return view('site.projects.show', [
            'project' => $project,
            'goal' => $this->linkedGoal($project['goal']),
        ]);
    }

    /**
     * The yearly goal a project serves, shaped for the parent chip. Hidden goals stay hidden.
     *
     * @return array{title: ?string, titleLength: ?int, anchor: string}|null
     */
    private function linkedGoal(?string $slug): ?array
    {
        $goal = array_find(PrototypeContent::yearlyGoals(), fn (array $goal): bool => $goal['slug'] === $slug);

        if ($goal === null) {
            return null;
        }

        $visibleGoal = GoalCensor::apply($goal);

        return [
            'title' => $visibleGoal['title'],
            'titleLength' => $visibleGoal['titleLength'],
            'anchor' => route('goals.index').'#hedef-'.$goal['slug'],
        ];
    }
}
