<?php

namespace App\Http\Controllers;

use App\Models\Goal;
use App\Support\Content\GoalContent;
use App\Support\Content\ProjectContent;
use Illuminate\View\View;

class ProjectsController extends Controller
{
    public function __construct(private ProjectContent $projects, private GoalContent $goals) {}

    /**
     * The featured project first, then the others in Kadir's order.
     */
    public function index(): View
    {
        $projects = collect($this->projects->all());
        $featured = $projects->firstWhere('isFeatured', true) ?? $projects->first();

        return view('site.projects.index', [
            'projectCount' => $projects->count(),
            'featured' => $featured,
            'projects' => $projects->reject(fn (array $project): bool => $project['slug'] === ($featured['slug'] ?? null))->values()->all(),
        ]);
    }

    /**
     * A case study with screenshots and the devlog; small projects show their summary only.
     */
    public function show(string $slug): View
    {
        $project = $this->projects->find($slug);

        abort_if($project === null, 404);

        return view('site.projects.show', [
            'project' => $project,
            'goal' => $this->goals->chip($project['goalId'] ? Goal::query()->find($project['goalId']) : null, onGoalsPage: false),
        ]);
    }
}
