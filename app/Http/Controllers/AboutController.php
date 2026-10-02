<?php

namespace App\Http\Controllers;

use App\Enums\GoalVisibility;
use App\Support\PrototypeContent;
use Illuminate\View\View;

class AboutController extends Controller
{
    /**
     * The story, a "now" list fed by the other sections, the toolbox and contact details.
     */
    public function __invoke(): View
    {
        $publicChains = array_values(array_filter(
            PrototypeContent::chains(),
            fn (array $chain): bool => $chain['visibility'] === GoalVisibility::Public,
        ));

        $books = array_find(PrototypeContent::yearlyGoals(), fn (array $goal): bool => $goal['slug'] === '12-kitap');

        return view('site.about', [
            'stops' => PrototypeContent::lifeStops(),
            'toolbox' => PrototypeContent::toolbox(),
            'now' => [
                'project' => PrototypeContent::featuredProject(),
                'series' => PrototypeContent::currentlyWatching()[0] ?? null,
                'chain' => $publicChains[0] ?? null,
                'post' => PrototypeContent::latestPost(),
                'books' => $books,
            ],
        ]);
    }
}
