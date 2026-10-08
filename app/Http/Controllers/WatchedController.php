<?php

namespace App\Http\Controllers;

use App\Enums\WatchableType;
use App\Support\Content\WatchedContent;
use Illuminate\View\View;

class WatchedController extends Controller
{
    public function __construct(private WatchedContent $watched) {}

    /**
     * Poster strip, the "currently watching" shelf and the diary grouped by month.
     */
    public function index(): View
    {
        $diary = collect($this->watched->diary());
        $thisYear = $diary->filter(fn (array $entry): bool => $entry['watchedAt']->isCurrentYear());

        return view('site.watched.index', [
            'filmCountThisYear' => $thisYear->where('type', WatchableType::Film)->unique('id')->count(),
            'seriesCountThisYear' => $thisYear->where('type', WatchableType::Series)->unique('id')->count(),
            'recent' => $diary->filter(WatchedContent::isDone(...))->unique('id')->take(6)->values()->all(),
            'currentlyWatching' => $this->watched->currentlyWatching(),
            'watchlist' => $this->watched->watchlist(),
            'diaryByMonth' => $diary->groupBy(fn (array $entry): string => $entry['watchedAt']->format('Y-m'))->all(),
        ]);
    }

    /**
     * One template for every film or series; the review sections appear only when there is a published review.
     */
    public function show(string $type, string $slug): View
    {
        $watchableType = WatchableType::fromRouteSegment($type);
        $entry = $watchableType ? $this->watched->find($watchableType, $slug) : null;

        abort_if($entry === null, 404);

        return view('site.watched.show', ['entry' => $entry]);
    }
}
