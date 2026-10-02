<?php

namespace App\Http\Controllers;

use App\Enums\GoalVisibility;
use App\Enums\ToolboxGroup;
use App\Models\Technology;
use App\Support\Content\ProjectContent;
use App\Support\PrototypeContent;
use Illuminate\View\View;

class AboutController extends Controller
{
    /**
     * The story, a "now" list fed by the other sections, the toolbox and contact details.
     */
    public function __invoke(ProjectContent $projects): View
    {
        $publicChains = array_values(array_filter(
            PrototypeContent::chains(),
            fn (array $chain): bool => $chain['visibility'] === GoalVisibility::Public,
        ));

        $books = array_find(PrototypeContent::yearlyGoals(), fn (array $goal): bool => $goal['slug'] === '12-kitap');

        return view('site.about', [
            'stops' => PrototypeContent::lifeStops(),
            'toolbox' => $this->toolbox(),
            'now' => [
                'project' => $projects->featured(),
                'series' => PrototypeContent::currentlyWatching()[0] ?? null,
                'chain' => $publicChains[0] ?? null,
                'post' => PrototypeContent::latestPost(),
                'books' => $books,
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
