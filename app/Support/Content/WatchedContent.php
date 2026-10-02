<?php

namespace App\Support\Content;

use App\Enums\Permission;
use App\Enums\SeriesStatus;
use App\Enums\WatchableType;
use App\Models\Season;
use App\Models\Viewing;
use App\Models\Watchable;
use App\Support\Images\PosterPalette;
use App\Support\Markdown\Markdown;
use App\Support\Og\OgUrl;
use Carbon\CarbonImmutable;
use Illuminate\Database\Eloquent\Builder;
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
 *     hasReview: bool, reviewHtml: ?HtmlString, reviewExcerpt: ?string, url: string, isDraft: bool, metaDescription: string, ogImage: string
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
     * @return WatchedEntry|null
     */
    public function lastWatched(): ?array
    {
        return $this->diary()[0] ?? null;
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
            ->where('type', WatchableType::Series)
            ->whereIn('series_status', array_map(fn (SeriesStatus $status): string => $status->value, array_filter(SeriesStatus::cases(), fn (SeriesStatus $status): bool => $status->isInProgress())))
            ->with(['seasons', 'viewings'])
            ->get()
            ->sortByDesc(fn (Watchable $watchable): string => ($watchable->viewings->first()->watched_on ?? $watchable->updated_at)?->toDateString() ?? '');

        return array_values($series->map(fn (Watchable $watchable): array => $this->toArray($watchable))->all());
    }

    /**
     * A published film or series, or a draft when the viewer may manage them.
     *
     * @return WatchedEntry|null
     */
    public function find(WatchableType $type, string $slug): ?array
    {
        $watchable = Watchable::query()->with(['seasons', 'viewings'])->where('type', $type)->where('slug', $slug)->first();

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
        $currentSeason = $watchable->seasons->firstWhere('number', $watchable->current_season);
        $palette = $watchable->poster_colors ?? app(PosterPalette::class)->palette(PosterPalette::FALLBACK_ACCENT)['colors'];

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
            'posterColors' => [(string) $palette[0], (string) $palette[1]],
            'accent' => $watchable->accent ?? PosterPalette::FALLBACK_ACCENT,
            'hasReview' => $reviewIsPublic,
            'reviewHtml' => $reviewIsPublic ? new HtmlString((string) $watchable->review_html) : null,
            'reviewExcerpt' => $reviewIsPublic ? $this->markdown->excerpt($this->withoutSpoilers((string) $watchable->review), 180) : null,
            'url' => route('watched.show', ['type' => $watchable->type->routeSegment(), 'slug' => $watchable->slug]),
            'isDraft' => ! $watchable->isPublished(),
            'metaDescription' => $watchable->meta_description ?: Str::limit((string) $watchable->overview, 155, '…', preserveWords: true),
            'ogImage' => OgUrl::for($watchable->type->routeSegment(), $watchable->slug, $watchable->updated_at),
        ];
    }

    /**
     * Excerpts must never give away a spoiler.
     */
    private function withoutSpoilers(string $review): string
    {
        return (string) preg_replace('/^:::[ \t]*(spoiler|replik).*?^:::[ \t]*$/msu', '', $review);
    }
}
