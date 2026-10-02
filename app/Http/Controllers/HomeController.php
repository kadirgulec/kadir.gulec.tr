<?php

namespace App\Http\Controllers;

use App\Support\Content\GoalContent;
use App\Support\Content\PostContent;
use App\Support\Content\ProjectContent;
use App\Support\Content\WatchedContent;
use Illuminate\View\View;

class HomeController extends Controller
{
    /**
     * The notebook cover: a "lately" board with one live snippet per section.
     */
    public function __invoke(ProjectContent $projects, PostContent $posts, WatchedContent $watched, GoalContent $goals): View
    {
        return view('site.home', [
            'latestPost' => $posts->latest(),
            'lastWatched' => $watched->lastWatched(),
            'currentlyWatching' => $watched->currentlyWatching(),
            'chains' => $goals->chains(14),
            'featuredProject' => $projects->featured(),
        ]);
    }
}
