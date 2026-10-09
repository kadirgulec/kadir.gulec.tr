<?php

namespace App\Http\Controllers;

use App\Support\Content\ReviewContent;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;

class MonthlyReviewsController extends Controller
{
    public function __construct(private ReviewContent $reviews) {}

    /**
     * The latest published review, or the empty page before the first one.
     */
    public function index(): RedirectResponse|View
    {
        $latest = $this->reviews->latestPublished();

        if ($latest !== null) {
            return redirect()->route('goals.reviews.show', $latest->monthKey());
        }

        return view('site.goals.review', ['review' => null]);
    }

    /**
     * One month: its numbers, what went well, what was hard, what comes next.
     */
    public function show(string $month): View
    {
        $review = $this->reviews->find($month);

        abort_if($review === null, 404);

        return view('site.goals.review', ['review' => $this->reviews->toArray($review)]);
    }
}
