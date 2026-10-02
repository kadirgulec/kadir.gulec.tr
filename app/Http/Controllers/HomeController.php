<?php

namespace App\Http\Controllers;

use App\Support\Content\PostContent;
use App\Support\Content\ProjectContent;
use App\Support\GoalCensor;
use App\Support\PrototypeContent;
use Illuminate\View\View;

class HomeController extends Controller
{
    /**
     * The notebook cover: a "lately" board with one live snippet per section.
     */
    public function __invoke(ProjectContent $projects, PostContent $posts): View
    {
        return view('site.home', [
            'latestPost' => $posts->latest(),
            'lastWatched' => PrototypeContent::lastWatched(),
            'currentlyWatching' => PrototypeContent::currentlyWatching(),
            'chains' => array_map(GoalCensor::apply(...), PrototypeContent::activeChains()),
            'featuredProject' => $projects->featured(),
        ]);
    }
}
