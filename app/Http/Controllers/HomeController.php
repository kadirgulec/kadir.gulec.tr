<?php

namespace App\Http\Controllers;

use App\Support\GoalCensor;
use App\Support\PrototypeContent;
use Illuminate\View\View;

class HomeController extends Controller
{
    /**
     * The notebook cover: a "lately" board with one live snippet per section.
     */
    public function __invoke(): View
    {
        return view('site.home', [
            'latestPost' => PrototypeContent::latestPost(),
            'lastWatched' => PrototypeContent::lastWatched(),
            'currentlyWatching' => PrototypeContent::currentlyWatching(),
            'chains' => array_map(GoalCensor::apply(...), PrototypeContent::activeChains()),
            'featuredProject' => PrototypeContent::featuredProject(),
        ]);
    }
}
