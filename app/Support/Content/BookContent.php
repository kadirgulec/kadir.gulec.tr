<?php

namespace App\Support\Content;

use App\Enums\BookFormat;
use App\Enums\Permission;
use App\Enums\ReadingStatus;
use App\Models\Book;
use App\Models\Quote;
use App\Models\Reading;
use App\Support\Images\PosterPalette;
use App\Support\Markdown\Markdown;
use Carbon\CarbonImmutable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\HtmlString;
use Illuminate\Support\Str;

/**
 * Books and readings in the shape the site views expect. A diary entry is one
 * finished or abandoned reading (with its book's facts); the detail page is
 * one book, seen through its latest reading. A book without any reading yet
 * (on the reading list, previewed by Kadir) has no status and no dates.
 *
 * @phpstan-type BookQuote array{text: string, by: ?string, location: ?string}
 * @phpstan-type BookReading array{startedOn: CarbonImmutable, finishedOn: ?CarbonImmutable, status: ReadingStatus, progress: ?int}
 * @phpstan-type BookEntry array{
 *     id: int, slug: string, title: string, originalTitle: ?string, author: string, translator: ?string, publisher: ?string,
 *     year: ?int, language: string, format: BookFormat, overview: ?string,
 *     coverUrl: ?string, coverColors: array{0: string, 1: string}, accent: string,
 *     rating: ?float, isFavorite: bool, status: ?ReadingStatus, progress: ?int,
 *     startedOn: ?CarbonImmutable, finishedOn: ?CarbonImmutable, readAt: ?CarbonImmutable, isReread: bool, readings: list<BookReading>,
 *     hasReview: bool, reviewHtml: ?HtmlString, reviewExcerpt: ?string, quotes: list<BookQuote>,
 *     url: string, isDraft: bool, metaDescription: string
 * }
 */
class BookContent
{
    public function __construct(private Markdown $markdown) {}

    /**
     * Every finished or abandoned reading of a published book, newest first.
     *
     * @return list<BookEntry>
     */
    public function diary(): array
    {
        $readings = $this->doneReadings()->with($this->bookRelations('book.'))->get();

        return array_values($readings->map(fn (Reading $reading): array => $this->toArray($reading->book, $reading))->all());
    }

    /**
     * The latest finished or abandoned readings, one per book, newest first.
     *
     * @return list<BookEntry>
     */
    public function recent(int $limit): array
    {
        $readings = $this->doneReadings()->cursor()->unique('book_id')->take($limit)->collect();
        $readings = (new Collection($readings->all()))->load($this->bookRelations('book.'));

        return array_values($readings->map(fn (Reading $reading): array => $this->toArray($reading->book, $reading))->all());
    }

    /**
     * Books being read or paused, most recently started first.
     *
     * @return list<BookEntry>
     */
    public function currentlyReading(): array
    {
        $books = Book::query()
            ->published()
            ->inProgress()
            ->with($this->bookRelations())
            ->get()
            ->sortByDesc(fn (Book $book): string => (string) $book->readings->first()?->started_on->toDateString());

        return array_values($books->map(fn (Book $book): array => $this->toArray($book))->all());
    }

    /**
     * The reading list ("okuyacaklarım"), in Kadir's order. Books on it are
     * often drafts, so they carry no link to a detail page.
     *
     * @return list<array{title: string, author: string, coverUrl: ?string, coverColors: array{0: string, 1: string}, note: ?string}>
     */
    public function toRead(): array
    {
        return array_values(Book::query()->onReadingList()->get()->map(fn (Book $book): array => [
            'title' => $book->title,
            'author' => $book->author,
            'coverUrl' => $book->coverUrl(480),
            'coverColors' => $book->coverPalette(),
            'note' => $book->reading_list_note,
        ])->all());
    }

    /**
     * How many readings of published books were finished in a year (abandoned ones do not count).
     */
    public function finishedCountIn(int $year): int
    {
        return Reading::query()
            ->where('status', ReadingStatus::Finished)
            ->whereYear('finished_on', $year)
            ->whereHas('book', fn (Builder $query) => $query->published())
            ->count();
    }

    /**
     * A published book, or a draft when the viewer may manage books.
     *
     * @return BookEntry|null
     */
    public function find(string $slug): ?array
    {
        $book = Book::query()->with($this->bookRelations())->where('slug', $slug)->first();

        if ($book === null || (! $book->isPublished() && ! Gate::allows(Permission::ManageBooks->value))) {
            return null;
        }

        return $this->toArray($book);
    }

    /**
     * One book, seen through one of its readings (its latest by default).
     *
     * @return BookEntry
     */
    public function toArray(Book $book, ?Reading $reading = null): array
    {
        $reading ??= $book->readings->first();
        $reviewIsPublic = $book->hasPublishedReview() || (filled($book->review_html) && Gate::allows(Permission::ManageBooks->value));

        return [
            'id' => $book->id,
            'slug' => $book->slug,
            'title' => $book->title,
            'originalTitle' => $book->original_title,
            'author' => $book->author,
            'translator' => $book->translator,
            'publisher' => $book->publisher,
            'year' => $book->year,
            'language' => $book->language,
            'format' => $book->format,
            'overview' => $book->overview,
            'coverUrl' => $book->coverUrl(480),
            'coverColors' => $book->coverPalette(),
            'accent' => $book->accent ?? PosterPalette::FALLBACK_ACCENT,
            'rating' => $book->rating,
            'isFavorite' => $book->is_favorite,
            'status' => $reading?->status,
            'progress' => $reading?->progress,
            'startedOn' => $reading?->started_on,
            'finishedOn' => $reading?->finished_on,
            'readAt' => $reading?->readOn(),
            'isReread' => $reading !== null && $book->readings->contains(fn (Reading $other): bool => $other->started_on->lessThan($reading->started_on) || ($other->started_on->equalTo($reading->started_on) && $other->id < $reading->id)),
            'readings' => array_values($book->readings->map(fn (Reading $other): array => [
                'startedOn' => $other->started_on,
                'finishedOn' => $other->finished_on,
                'status' => $other->status,
                'progress' => $other->progress,
            ])->all()),
            'hasReview' => $reviewIsPublic,
            'reviewHtml' => $reviewIsPublic ? new HtmlString((string) $book->review_html) : null,
            'reviewExcerpt' => $reviewIsPublic ? $this->markdown->excerpt($this->withoutSpoilers((string) $book->review), 180) : null,
            'quotes' => array_values($book->quotes->filter(fn (Quote $quote): bool => $quote->isPublished())->map(fn (Quote $quote): array => [
                'text' => $quote->body,
                'by' => $quote->said_by,
                'location' => $quote->location,
            ])->all()),
            'url' => url($book->publicPath()),
            'isDraft' => ! $book->isPublished(),
            'metaDescription' => $book->meta_description ?: Str::limit((string) $book->overview, 155, '…', preserveWords: true),
        ];
    }

    /**
     * Finished and abandoned readings of published books, newest first.
     *
     * @return Builder<Reading>
     */
    private function doneReadings(): Builder
    {
        return Reading::query()
            ->whereIn('status', [ReadingStatus::Finished, ReadingStatus::Abandoned])
            ->whereHas('book', fn (Builder $query) => $query->published())
            ->orderByDesc('finished_on')
            ->orderByDesc('id');
    }

    /**
     * What toArray() reads from a book: every reading and the quotes.
     *
     * @return list<string>
     */
    private function bookRelations(string $prefix = ''): array
    {
        return [$prefix.'readings', $prefix.'quotes'];
    }

    /**
     * Excerpts must never give away a spoiler, nor start with a quote block.
     */
    private function withoutSpoilers(string $review): string
    {
        return (string) preg_replace('/^:::[ \t]*(spoiler|alıntı).*?^:::[ \t]*$/msu', '', $review);
    }
}
