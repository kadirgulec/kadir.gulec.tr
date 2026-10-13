<?php

namespace App\Http\Controllers;

use App\Support\Content\BookContent;
use App\Support\Content\GoalContent;
use App\Support\Content\NoteContent;
use Illuminate\View\View;

class BooksController extends Controller
{
    public function __construct(private BookContent $books) {}

    /**
     * Cover strip, the "currently reading" shelf, the reading list and the diary grouped by year.
     */
    public function index(GoalContent $goals): View
    {
        $diary = collect($this->books->diary());

        return view('site.books.index', [
            'finishedThisYear' => $this->books->finishedCountIn(now()->year),
            'readingGoal' => $goals->readingGoal(),
            'recent' => $this->books->recent(6),
            'currentlyReading' => $this->books->currentlyReading(),
            'toRead' => $this->books->toRead(),
            'diaryByYear' => $diary->groupBy(fn (array $entry): int => $entry['readAt']->year)->all(),
        ]);
    }

    /**
     * One template for every book; the review sections appear only when there is a review.
     */
    public function show(string $slug, NoteContent $notes): View
    {
        $entry = $this->books->find($slug);

        abort_if($entry === null, 404);

        return view('site.books.show', [
            'entry' => $entry,
            'notes' => $notes->forSource('book', $entry['id']),
        ]);
    }
}
