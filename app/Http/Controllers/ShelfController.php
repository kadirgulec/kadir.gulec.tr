<?php

namespace App\Http\Controllers;

use App\Enums\WatchableType;
use App\Support\Content\BookContent;
use App\Support\Content\WatchedContent;
use Carbon\CarbonImmutable;
use Illuminate\View\View;

class ShelfController extends Controller
{
    /**
     * The shelf: what is being read and watched now, what comes next, and one
     * diary of films, series and books grouped by month.
     */
    public function __invoke(WatchedContent $watched, BookContent $books): View
    {
        $viewings = collect($watched->diary());
        $viewingsThisYear = $viewings->filter(fn (array $entry): bool => $entry['watchedAt']->isCurrentYear());

        /** @var list<array{kind: 'watched'|'book', date: CarbonImmutable, entry: array<string, mixed>}> $diary */
        $diary = $viewings->map(fn (array $entry): array => ['kind' => 'watched', 'date' => $entry['watchedAt'], 'entry' => $entry])
            ->concat(collect($books->diary())->map(fn (array $entry): array => ['kind' => 'book', 'date' => $entry['readAt'], 'entry' => $entry]))
            ->sortByDesc(fn (array $item): string => $item['date']->toDateString())
            ->values()
            ->all();

        return view('site.shelf.index', [
            'filmCountThisYear' => $viewingsThisYear->where('type', WatchableType::Film)->unique('id')->count(),
            'seriesCountThisYear' => $viewingsThisYear->where('type', WatchableType::Series)->unique('id')->count(),
            'bookCountThisYear' => $books->finishedCountIn(now()->year),
            'currentlyReading' => $books->currentlyReading(),
            'currentlyWatching' => $watched->currentlyWatching(),
            'toRead' => $books->toRead(),
            'watchlist' => $watched->watchlist(),
            'diaryByMonth' => collect($diary)->groupBy(fn (array $item): string => $item['date']->format('Y-m'))->all(),
        ]);
    }
}
