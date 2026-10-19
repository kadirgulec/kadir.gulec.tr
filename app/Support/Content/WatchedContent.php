<?php

namespace App\Support\Content;

use App\Enums\Permission;
use App\Enums\SeriesStatus;
use App\Enums\WatchableType;
use App\Models\Quote;
use App\Models\Season;
use App\Models\Viewing;
use App\Models\Watchable;
use App\Support\Images\PosterPalette;
use App\Support\Markdown\Markdown;
use App\Support\Og\OgUrl;
use Carbon\CarbonImmutable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\HtmlString;
use Illuminate\Support\Str;

/**
 * Films and series in the shape the site views expect. A diary entry is one
 * viewing (with its film's facts); the detail page is one film or series.
 *
 * @phpstan-type WatchedEntry array{
 *     id: int, type: WatchableType, slug: string, title: string, originalTitle: ?string, year: ?int, creator: ?string,
 *     genres: list<string>, runtimeMinutes: ?int, overview: ?string, cast: list<array{name: string, role: string}>,
 *     watchedAt: CarbonImmutable, place: ?string, note: ?string, rating: ?float, isFavorite: bool, isRewatch: bool,
 *     status: ?SeriesStatus, season: ?int, episode: ?int, episodeCount: ?int,
 *     seasons: list<array{number: int, episodeCount: int, rating: ?float, note: ?string}>,
 *     posterUrl: ?string, posterColors: array{0: string, 1: string}, accent: string,
 *     hasReview: bool, isReviewDraft: bool, reviewHtml: ?HtmlString, reviewExcerpt: ?string, quotes: list<array{text: string, by: ?string, location: ?string}>,
 *     url: string, isDraft: bool, metaDescription: string, ogImage: string
 * }
 */
class WatchedContent
{
    public function __construct(private Markdown $markdown) {}

    /**
     * Every viewing of a published film or series, newest first.
     *
     * @return list<WatchedEntry>
     */
    public function diary(): array
    {
        $viewings = Viewing::query()
            ->whereHas('watchable', fn (Builder $query) => $query->published())
            ->with(['watchable.seasons', 'watchable.viewings'])
            ->orderByDesc('watched_on')
            ->orderByDesc('id')
            ->get();

        return array_values($viewings->map(fn (Viewing $viewing): array => $this->toArray($viewing->watchable, $viewing))->all());
    }

    /**
     * The latest viewings of films and series Kadir is done with (finished or
     * dropped), one per title, newest first. A series still in progress lives
     * on the "currently watching" shelf instead.
     *
     * @return list<WatchedEntry>
     */
    public function recent(int $limit): array
    {
        $viewings = Viewing::query()
            ->whereHas('watchable', fn (Builder $query) => $query->published()->done())
            ->orderByDesc('watched_on')
            ->orderByDesc('id')
            ->cursor()
            ->unique('watchable_id')
            ->take($limit)
            ->all();

        $viewings = (new Collection($viewings))->load(['watchable.seasons', 'watchable.viewings']);

        return array_values($viewings->map(fn (Viewing $viewing): array => $this->toArray($viewing->watchable, $viewing))->all());
    }

    /**
     * @return WatchedEntry|null
     */
    public function lastWatched(): ?array
    {
        return $this->recent(1)[0] ?? null;
    }

    /**
     * Series in progress (watching or paused), most recently watched first.
     *
     * @return list<WatchedEntry>
     */
    public function currentlyWatching(): array
    {
        $series = Watchable::query()
            ->published()
            ->inProgress()
            ->with(['seasons', 'viewings'])
            ->get()
            ->sortByDesc(fn (Watchable $watchable): string => ($watchable->viewings->first()->watched_on ?? $watchable->updated_at)?->toDateString() ?? '');

        return array_values($series->map(fn (Watchable $watchable): array => $this->toArray($watchable))->all());
    }

    /**
     * The watchlist ("sırada"), in Kadir's order. Entries are often drafts, so
     * they carry no link to a detail page.
     *
     * @return list<array{title: string, type: WatchableType, year: ?int, posterUrl: ?string, posterColors: array{0: string, 1: string}, note: ?string}>
     */
    public function watchlist(): array
    {
        return array_values(Watchable::query()->onWatchlist()->get()->map(fn (Watchable $watchable): array => [
            'title' => $watchable->title,
            'type' => $watchable->type,
            'year' => $watchable->year,
            'posterUrl' => $watchable->posterUrl(480),
            'posterColors' => $watchable->posterPalette(),
            'note' => $watchable->watchlist_note,
        ])->all());
    }

    /**
     * A published film or series, or a draft when the viewer may manage them.
     *
     * @return WatchedEntry|null
     */
    public function find(WatchableType $type, string $slug): ?array
    {
        $watchable = Watchable::query()->with(['seasons', 'viewings', 'quotes'])->where('type', $type)->where('slug', $slug)->first();

        if ($watchable === null || (! $watchable->isPublished() && ! Gate::allows(Permission::ManageWatched->value))) {
            return null;
        }

        return $this->toArray($watchable);
    }

    /**
     * @return WatchedEntry
     */
    public function toArray(Watchable $watchable, ?Viewing $viewing = null): array
    {
        $viewing ??= $watchable->viewings->first();
        $reviewIsPublic = $watchable->hasPublishedReview() || (filled($watchable->review_html) && Gate::allows(Permission::ManageWatched->value));
        $reviewExcerpt = $reviewIsPublic ? $this->markdown->excerpt($this->withoutSpoilers((string) $watchable->review), 180) : null;
        $currentSeason = $watchable->seasons->firstWhere('number', $watchable->current_season);

        return [
            'id' => $watchable->id,
            'type' => $watchable->type,
            'slug' => $watchable->slug,
            'title' => $watchable->title,
            'originalTitle' => $watchable->original_title,
            'year' => $watchable->year,
            'creator' => $watchable->creator,
            'genres' => $watchable->genres ?? [],
            'runtimeMinutes' => $watchable->runtime_minutes,
            'overview' => $watchable->overview,
            'cast' => $watchable->cast ?? [],
            'watchedAt' => $viewing->watched_on ?? $watchable->created_at ?? now(),
            'place' => $viewing?->place,
            'note' => $viewing?->note,
            'rating' => $watchable->rating,
            'isFavorite' => $watchable->is_favorite,
            'isRewatch' => $viewing !== null && $watchable->viewings->contains(fn (Viewing $other): bool => $other->watched_on->lessThan($viewing->watched_on) || ($other->watched_on->equalTo($viewing->watched_on) && $other->id < $viewing->id)),
            'status' => $watchable->isSeries() ? $watchable->series_status : null,
            'season' => $watchable->isSeries() ? $watchable->current_season : null,
            'episode' => $watchable->isSeries() ? $watchable->current_episode : null,
            'episodeCount' => $currentSeason?->episode_count,
            'seasons' => array_values($watchable->seasons->map(fn (Season $season): array => [
                'number' => $season->number,
                'episodeCount' => $season->episode_count,
                'rating' => $season->rating,
                'note' => $season->note,
            ])->all()),
            'posterUrl' => $watchable->posterUrl(480),
            'posterColors' => $watchable->posterPalette(),
            'accent' => $watchable->accent ?? PosterPalette::FALLBACK_ACCENT,
            'hasReview' => $reviewIsPublic,
            // Kadir previews an unpublished review; the page labels it so it is not mistaken for a published one.
            'isReviewDraft' => $reviewIsPublic && ! $watchable->hasPublishedReview(),
            'reviewHtml' => $reviewIsPublic ? new HtmlString((string) $watchable->review_html) : null,
            'reviewExcerpt' => $reviewExcerpt,
            // Only the detail page loads the quotes; lists never show them.
            'quotes' => $watchable->relationLoaded('quotes') ? array_values($watchable->quotes->filter(fn (Quote $quote): bool => $quote->isPublished())->map(fn (Quote $quote): array => [
                'text' => $quote->body,
                'by' => $quote->said_by,
                'location' => $quote->location,
            ])->all()) : [],
            'url' => url($watchable->publicPath()),
            'isDraft' => ! $watchable->isPublished(),
            'metaDescription' => $this->metaDescription($watchable, $reviewExcerpt),
            'ogImage' => OgUrl::for($watchable->type->routeSegment(), $watchable->slug, $watchable->updated_at),
        ];
    }

    /**
     * Excerpts must never give away a spoiler.
     */
    /**
     * What search results show under the title: Kadir's own description, else
     * the overview, else the public review, else a sentence built from the facts.
     */
    private function metaDescription(Watchable $watchable, ?string $reviewExcerpt): string
    {
        if (filled($watchable->meta_description)) {
            return $watchable->meta_description;
        }

        $text = Arr::first([$watchable->overview, $reviewExcerpt], fn (?string $text): bool => filled($text));

        if ($text !== null) {
            return Str::limit(trim($text), 155, '…', preserveWords: true);
        }

        $facts = implode(', ', array_filter([
            $watchable->year ? "{$watchable->title} ({$watchable->year})" : $watchable->title,
            $watchable->creator,
        ]));

        return "Kadir'in izlediklerinden bir ".Str::lower($watchable->type->label()).": {$facts}.";
    }

    private function withoutSpoilers(string $review): string
    {
        return (string) preg_replace('/^:::[ \t]*(spoiler|replik).*?^:::[ \t]*$/msu', '', $review);
    }
}
