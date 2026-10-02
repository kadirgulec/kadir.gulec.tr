<?php

namespace App\Http\Controllers;

use App\Enums\ToolboxGroup;
use App\Models\Technology;
use App\Support\Content\GoalContent;
use App\Support\Content\PostContent;
use App\Support\Content\ProjectContent;
use App\Support\Content\WatchedContent;
use Illuminate\View\View;

class AboutController extends Controller
{
    /**
     * The story, a "now" list fed by the other sections, the toolbox and contact details.
     */
    public function __invoke(ProjectContent $projects, PostContent $posts, WatchedContent $watched, GoalContent $goals): View
    {
        return view('site.about', [
            'stops' => config('about.stops'),
            'toolbox' => $this->toolbox(),
            'now' => [
                'project' => $projects->featured(),
                'series' => $watched->currentlyWatching()[0] ?? null,
                'chain' => $goals->firstPublicChain(),
                'post' => $posts->latest(),
                'books' => $goals->readingGoal(),
            ],
        ]);
    }

    /**
     * The toolbox stickers, from the technologies Kadir put into a toolbox group.
     *
     * @return array{daily: list<string>, sometimes: list<string>, languages: list<string>}
     */
    private function toolbox(): array
    {
        $technologies = Technology::query()
            ->whereNotNull('toolbox_group')
            ->orderBy('toolbox_order')
            ->orderBy('name')
            ->get();

        $names = fn (ToolboxGroup $group): array => array_values($technologies
            ->filter(fn (Technology $technology): bool => $technology->toolbox_group === $group)
            ->map(fn (Technology $technology): string => $technology->name)
            ->all());

        return [
            'daily' => $names(ToolboxGroup::Daily),
            'sometimes' => $names(ToolboxGroup::Sometimes),
            'languages' => $names(ToolboxGroup::Languages),
        ];
    }
}
