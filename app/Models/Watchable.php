<?php

namespace App\Models;

use App\Enums\SeriesStatus;
use App\Enums\WatchableType;
use App\Models\Concerns\HasFollowers;
use App\Models\Concerns\HasPublication;
use App\Models\Concerns\HasSlugRedirects;
use App\Models\Concerns\RendersMarkdown;
use App\Models\Concerns\Sortable;
use App\Support\Images\ImageStore;
use App\Support\Images\PosterPalette;
use Carbon\CarbonImmutable;
use Database\Factories\WatchableFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Scope;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

/**
 * A film or a series: its facts (mostly from TMDB, stored locally) and
 * Kadir's take on it (one current rating, favorite, review, series progress).
 * Each time it was watched is a Viewing; a series has Seasons.
 *
 * @property int $id
 * @property WatchableType $type
 * @property string $slug
 * @property int|null $tmdb_id
 * @property string $title
 * @property string|null $original_title
 * @property int|null $year
 * @property string|null $creator
 * @property list<string>|null $genres
 * @property int|null $runtime_minutes
 * @property string|null $overview
 * @property list<array{name: string, role: string}>|null $cast
 * @property string|null $poster_path
 * @property string|null $accent
 * @property array{0: string, 1: string}|null $poster_colors
 * @property float|null $rating
 * @property bool $is_favorite
 * @property string|null $review
 * @property string|null $review_html
 * @property CarbonImmutable|null $review_published_at
 * @property SeriesStatus|null $series_status
 * @property int|null $current_season
 * @property int|null $current_episode
 * @property int|null $watchlist_position
 * @property string|null $watchlist_note
 * @property string|null $meta_description
 * @property CarbonImmutable|null $created_at
 * @property CarbonImmutable|null $updated_at
 */
#[Fillable([
    'type', 'slug', 'tmdb_id', 'title', 'original_title', 'year', 'creator', 'genres', 'runtime_minutes', 'overview', 'cast',
    'rating', 'is_favorite', 'review', 'review_published_at', 'series_status', 'current_season', 'current_episode',
    'meta_description', 'published_at', 'watchlist_note',
])]
class Watchable extends Model
{
    /** @use HasFactory<WatchableFactory> */
    use HasFactory, HasFollowers, HasPublication, HasSlugRedirects, RendersMarkdown, Sortable;

    protected static function booted(): void
    {
        static::deleting(fn (Watchable $watchable) => app(ImageStore::class)->delete($watchable->poster_path));
    }

    /**
     * @return HasMany<Viewing, $this>
     */
    public function viewings(): HasMany
    {
        return $this->hasMany(Viewing::class)->orderByDesc('watched_on')->orderByDesc('id');
    }

    /**
     * @return HasOne<Viewing, $this>
     */
    public function latestViewing(): HasOne
    {
        return $this->hasOne(Viewing::class)->ofMany(['watched_on' => 'max', 'id' => 'max']);
    }

    /**
     * @return HasMany<Season, $this>
     */
    public function seasons(): HasMany
    {
        return $this->hasMany(Season::class)->orderBy('number');
    }

    public function isSeries(): bool
    {
        return $this->type === WatchableType::Series;
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
     * The watchlist ("izleyeceğim"), in Kadir's order.
     *
     * @param  Builder<static>  $query
     */
    #[Scope]
    protected function onWatchlist(Builder $query): void
    {
        $query->whereNotNull('watchlist_position')->orderBy('watchlist_position')->orderBy('id');
    }

    /**
     * Series on the "currently watching" shelf (watching or paused).
     *
     * @param  Builder<static>  $query
     */
    #[Scope]
    protected function inProgress(Builder $query): void
    {
        $query->whereIn('series_status', SeriesStatus::inProgress());
    }

    /**
     * Films, and series Kadir finished or dropped: everything off the shelf.
     *
     * @param  Builder<static>  $query
     */
    #[Scope]
    protected function done(Builder $query): void
    {
        $query->where(fn (Builder $query) => $query->whereNull('series_status')->orWhereNotIn('series_status', SeriesStatus::inProgress()));
    }

    public function isOnWatchlist(): bool
    {
        return $this->watchlist_position !== null;
    }

    /**
     * Puts the film or series at the end of the watchlist (a note can say who recommended it).
     */
    public function addToWatchlist(?string $note = null): void
    {
        if (! $this->isOnWatchlist()) {
            $this->watchlist_position = $this->nextSortOrder();
        }

        if ($note !== null) {
            $this->watchlist_note = $note !== '' ? $note : null;
        }

        $this->save();
    }

    public function removeFromWatchlist(): void
    {
        $this->watchlist_position = null;
        $this->watchlist_note = null;
        $this->save();
    }

    /**
     * Moves the film or series to a zero-based position on the watchlist and renumbers the list.
     */
    public function moveInWatchlist(int $position): void
    {
        if ($this->isOnWatchlist()) {
            $this->moveTo($position);
        }
    }

    /**
     * The two colors of the drawn poster ([dark, accent]), also when no poster was stored.
     *
     * @return array{0: string, 1: string}
     */
    public function posterPalette(): array
    {
        $colors = $this->poster_colors ?? app(PosterPalette::class)->palette(PosterPalette::FALLBACK_ACCENT)['colors'];

        return [(string) $colors[0], (string) $colors[1]];
    }

    public function posterUrl(int $width = 960): ?string
    {
        return ImageStore::url($this->poster_path, $width);
    }

    public function publicPath(?string $slug = null): string
    {
        return '/izlediklerim/'.$this->type->routeSegment().'/'.($slug ?? $this->slug);
    }

    protected function sortColumn(): string
    {
        return 'watchlist_position';
    }

    /**
     * @param  Builder<static>  $query
     * @return Builder<static>
     */
    protected function sortSiblings(Builder $query): Builder
    {
        return $query->whereNotNull('watchlist_position');
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
            'type' => WatchableType::class,
            'series_status' => SeriesStatus::class,
            'genres' => 'array',
            'cast' => 'array',
            'poster_colors' => 'array',
            'rating' => 'float',
            'is_favorite' => 'boolean',
            'year' => 'integer',
            'runtime_minutes' => 'integer',
            'tmdb_id' => 'integer',
            'current_season' => 'integer',
            'current_episode' => 'integer',
            'watchlist_position' => 'integer',
            'review_published_at' => 'immutable_datetime',
        ];
    }
}
