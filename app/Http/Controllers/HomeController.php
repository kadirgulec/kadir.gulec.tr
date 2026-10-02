<?php

namespace App\Http\Controllers;

use App\Support\Content\PostContent;
use App\Support\Content\ProjectContent;
use App\Support\Content\WatchedContent;
use App\Support\GoalCensor;
use App\Support\PrototypeContent;
use Illuminate\View\View;

class HomeController extends Controller
{
    /**
     * The notebook cover: a "lately" board with one live snippet per section.
     */
    public function __invoke(ProjectContent $projects, PostContent $posts, WatchedContent $watched): View
    {
        return view('site.home', [
            'latestPost' => $posts->latest(),
            'lastWatched' => $watched->lastWatched(),
            'currentlyWatching' => $watched->currentlyWatching(),
            'chains' => array_map(GoalCensor::apply(...), PrototypeContent::activeChains()),
            'featuredProject' => $projects->featured(),
        ]);
    }
}
