<?php

namespace App\Models;

use App\Enums\BookFormat;
use App\Enums\ReadingStatus;
use App\Models\Concerns\HasPublication;
use App\Models\Concerns\HasQuotes;
use App\Models\Concerns\HasSlugRedirects;
use App\Models\Concerns\RendersMarkdown;
use App\Models\Concerns\Sortable;
use App\Support\Images\ImageStore;
use App\Support\Images\PosterPalette;
use Carbon\CarbonImmutable;
use Database\Factories\BookFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Scope;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Support\Str;

/**
 * A book: its facts (often from Open Library, stored locally) and Kadir's
 * take on it (one current rating, favorite, review). Each time it was read
 * is a Reading; quotes are collected apart from the review.
 *
 * @property int $id
 * @property string $slug
 * @property string|null $isbn
 * @property string|null $open_library_key
 * @property string $title
 * @property string|null $original_title
 * @property string $author
 * @property string|null $translator
 * @property string|null $publisher
 * @property int|null $year
 * @property string $language
 * @property BookFormat $format
 * @property string|null $overview
 * @property string|null $cover_path
 * @property string|null $accent
 * @property array{0: string, 1: string}|null $cover_colors
 * @property float|null $rating
 * @property bool $is_favorite
 * @property string|null $review
 * @property string|null $review_html
 * @property CarbonImmutable|null $review_published_at
 * @property int|null $reading_list_position
 * @property string|null $reading_list_note
 * @property string|null $meta_description
 * @property CarbonImmutable|null $created_at
 * @property CarbonImmutable|null $updated_at
 */
#[Fillable([
    'slug', 'isbn', 'open_library_key', 'title', 'original_title', 'author', 'translator', 'publisher', 'year', 'language',
    'format', 'overview', 'rating', 'is_favorite', 'review', 'review_published_at', 'meta_description', 'published_at',
    'reading_list_note',
])]
class Book extends Model
{
    /** @use HasFactory<BookFactory> */
    use HasFactory, HasPublication, HasQuotes, RendersMarkdown, Sortable;

    use HasSlugRedirects {
        uniqueSlug as numberedSlug;
    }

    protected $attributes = [
        'language' => 'Türkçe',
        'format' => 'ebook',
    ];

    protected static function booted(): void
    {
        static::deleting(fn (Book $book) => app(ImageStore::class)->delete($book->cover_path));
    }

    /**
     * @return HasMany<Reading, $this>
     */
    public function readings(): HasMany
    {
        return $this->hasMany(Reading::class)->orderByDesc('started_on')->orderByDesc('id');
    }

    /**
     * @return HasOne<Reading, $this>
     */
    public function latestReading(): HasOne
    {
        return $this->hasOne(Reading::class)->ofMany(['started_on' => 'max', 'id' => 'max']);
    }

    /**
     * The review is visible once it has a text and its own publication time passed.
     */
    public function hasPublishedReview(): bool
    {
        return filled($this->review_html)
            && $this->review_published_at !== null
            && ! $this->review_published_at->isFuture();
    }

    /**
     * Books being read or paused right now: the "currently reading" shelf.
     *
     * @param  Builder<static>  $query
     */
    #[Scope]
    protected function inProgress(Builder $query): void
    {
        $query->whereHas('latestReading', fn (Builder $query) => $query->whereIn('status', ReadingStatus::inProgress()));
    }

    /**
     * The reading list ("okuyacaklarım"), in Kadir's order.
     *
     * @param  Builder<static>  $query
     */
    #[Scope]
    protected function onReadingList(Builder $query): void
    {
        $query->whereNotNull('reading_list_position')->orderBy('reading_list_position')->orderBy('id');
    }

    public function isOnReadingList(): bool
    {
        return $this->reading_list_position !== null;
    }

    /**
     * Puts the book at the end of the reading list (a note can say who recommended it).
     */
    public function addToReadingList(?string $note = null): void
    {
        if (! $this->isOnReadingList()) {
            $this->reading_list_position = $this->nextSortOrder();
        }

        if ($note !== null) {
            $this->reading_list_note = $note !== '' ? $note : null;
        }

        $this->save();
    }

    public function removeFromReadingList(): void
    {
        $this->reading_list_position = null;
        $this->reading_list_note = null;
        $this->save();
    }

    /**
     * Moves the book to a zero-based position on the reading list and renumbers the list.
     */
    public function moveInReadingList(int $position): void
    {
        if ($this->isOnReadingList()) {
            $this->moveTo($position);
        }
    }

    /**
     * The two colors of the drawn cover ([dark, accent]), also when no cover was stored.
     *
     * @return array{0: string, 1: string}
     */
    public function coverPalette(): array
    {
        $colors = $this->cover_colors ?? app(PosterPalette::class)->palette(PosterPalette::FALLBACK_ACCENT)['colors'];

        return [(string) $colors[0], (string) $colors[1]];
    }

    public function coverUrl(int $width = 960): ?string
    {
        return ImageStore::url($this->cover_path, $width);
    }

    public function publicPath(?string $slug = null): string
    {
        return '/okuduklarim/'.($slug ?? $this->slug);
    }

    /**
     * Two books with the same title tell each other apart by their author
     * ("baslik-yazar"), which says more than a number; only then a number.
     */
    public function uniqueSlug(string $base): string
    {
        $base = $base !== '' ? $base : 'kayit';

        if (! $this->slugIsTaken($base) || blank($this->author)) {
            return $this->numberedSlug($base);
        }

        return $this->numberedSlug($base.'-'.Str::slug($this->author));
    }

    private function slugIsTaken(string $slug): bool
    {
        return static::query()->where('slug', $slug)->when($this->exists, fn ($query) => $query->whereKeyNot($this->getKey()))->exists();
    }

    protected function sortColumn(): string
    {
        return 'reading_list_position';
    }

    /**
     * @param  Builder<static>  $query
     * @return Builder<static>
     */
    protected function sortSiblings(Builder $query): Builder
    {
        return $query->whereNotNull('reading_list_position');
    }

    protected function slugSource(): string
    {
        return 'title';
    }

    protected function markdownColumns(): array
    {
        return ['review' => 'review_html'];
    }

    protected function casts(): array
    {
        return [
            'format' => BookFormat::class,
            'cover_colors' => 'array',
            'rating' => 'float',
            'is_favorite' => 'boolean',
            'year' => 'integer',
            'reading_list_position' => 'integer',
            'review_published_at' => 'immutable_datetime',
        ];
    }
}
