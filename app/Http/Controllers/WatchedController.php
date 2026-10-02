<?php

namespace App\Http\Controllers;

use App\Enums\WatchableType;
use App\Support\PrototypeContent;
use Illuminate\View\View;

class WatchedController extends Controller
{
    /**
     * Poster strip, the "currently watching" shelf and the diary grouped by month.
     */
    public function index(): View
    {
        $diary = collect(PrototypeContent::watchedDiary());
        $thisYear = $diary->filter(fn (array $entry): bool => $entry['watchedAt']->isCurrentYear());

        return view('site.watched.index', [
            'filmCountThisYear' => $thisYear->where('type', WatchableType::Film)->count(),
            'seriesCountThisYear' => $thisYear->where('type', WatchableType::Series)->count(),
            'recent' => $diary->take(6)->all(),
            'currentlyWatching' => PrototypeContent::currentlyWatching(),
            'diaryByMonth' => $diary->groupBy(fn (array $entry): string => $entry['watchedAt']->format('Y-m'))->all(),
        ]);
    }

    /**
     * One template for every film or series; the review sections appear only when there is a review.
     */
    public function show(string $type, string $slug): View
    {
        $watchableType = WatchableType::fromRouteSegment($type);
        $entry = $watchableType ? PrototypeContent::findWatched($watchableType, $slug) : null;

        abort_if($entry === null, 404);

        return view('site.watched.show', ['entry' => $entry]);
    }
}
