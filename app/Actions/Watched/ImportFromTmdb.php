<?php

namespace App\Actions\Watched;

use App\Enums\WatchableType;
use App\Models\Watchable;
use App\Support\Tmdb\Tmdb;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use Throwable;

/**
 * Creates a film or series from TMDB, or refreshes the facts of an existing
 * one (e.g. after a new season). Kadir's own data (rating, review, season
 * notes, viewings) is never touched; the poster is only downloaded when
 * there is none yet.
 */
class ImportFromTmdb
{
    public function __construct(private Tmdb $tmdb, private StorePoster $storePoster) {}

    public function handle(WatchableType $type, int $tmdbId, ?Watchable $watchable = null): Watchable
    {
        if ($watchable === null && Watchable::query()->where('type', $type)->where('tmdb_id', $tmdbId)->exists()) {
            throw ValidationException::withMessages(['tmdb' => 'Bu kayıt zaten var.']);
        }

        $details = $this->tmdb->details($type, $tmdbId);

        $watchable = DB::transaction(function () use ($type, $details, $watchable): Watchable {
            $watchable ??= new Watchable(['type' => $type]);

            $watchable->fill([
                'tmdb_id' => $details['tmdbId'],
                'title' => $details['title'],
                'original_title' => $details['originalTitle'],
                'year' => $details['year'],
                'creator' => $details['creator'],
                'genres' => $details['genres'],
                'runtime_minutes' => $details['runtimeMinutes'],
                'overview' => $details['overview'],
                'cast' => $details['cast'],
            ])->save();

            foreach ($details['seasons'] as $season) {
                $watchable->seasons()->updateOrCreate(['number' => $season['number']], ['episode_count' => $season['episodeCount']]);
            }

            return $watchable;
        });

        if ($watchable->poster_path === null && $details['posterPath'] !== null) {
            try {
                $file = $this->tmdb->downloadPoster($details['posterPath']);
                $this->storePoster->handle($watchable, $file);
                @unlink($file);
            } catch (Throwable $exception) {
                report($exception);
            }
        }

        return $watchable;
    }
}
