<?php

namespace App\Support\Tmdb;

use App\Enums\WatchableType;
use Illuminate\Http\Client\PendingRequest;
use Illuminate\Support\Facades\Http;
use RuntimeException;

/**
 * The Movie Database (TMDB) API, used only from the admin panel: search,
 * then import the facts of one film or series. Site pages never call TMDB;
 * everything is stored locally, the poster included.
 *
 * @phpstan-type SearchResult array{id: int, title: string, originalTitle: ?string, year: ?int, posterUrl: ?string, overview: string}
 * @phpstan-type Details array{
 *     tmdbId: int, title: string, originalTitle: ?string, year: ?int, creator: ?string, genres: list<string>,
 *     runtimeMinutes: ?int, overview: ?string, cast: list<array{name: string, role: string}>,
 *     seasons: list<array{number: int, episodeCount: int}>, posterPath: ?string
 * }
 */
class Tmdb
{
    private const BASE_URL = 'https://api.themoviedb.org/3';

    private const IMAGE_URL = 'https://image.tmdb.org/t/p/';

    private const CAST_LIMIT = 8;

    public function isConfigured(): bool
    {
        return filled(config('services.tmdb.token'));
    }

    /**
     * @return list<SearchResult>
     */
    public function search(string $query, WatchableType $type): array
    {
        $results = $this->request()->get('/search/'.$this->segment($type), [
            'query' => $query,
            'language' => 'tr-TR',
            'include_adult' => 'false',
        ])->throw()->json('results', []);

        return array_values(array_map(fn (array $result): array => [
            'id' => (int) $result['id'],
            'title' => (string) ($result['title'] ?? $result['name'] ?? ''),
            'originalTitle' => $result['original_title'] ?? $result['original_name'] ?? null,
            'year' => $this->year($result['release_date'] ?? $result['first_air_date'] ?? null),
            'posterUrl' => isset($result['poster_path']) ? self::IMAGE_URL.'w185'.$result['poster_path'] : null,
            'overview' => (string) ($result['overview'] ?? ''),
        ], array_slice($results, 0, 12)));
    }

    /**
     * @return Details
     */
    public function details(WatchableType $type, int $tmdbId): array
    {
        $data = $this->request()->get('/'.$this->segment($type).'/'.$tmdbId, [
            'language' => 'tr-TR',
            'append_to_response' => 'credits',
        ])->throw()->json();

        $overview = trim((string) ($data['overview'] ?? ''));

        if ($overview === '') {
            $overview = trim((string) $this->request()->get('/'.$this->segment($type).'/'.$tmdbId, ['language' => 'en-US'])->json('overview', ''));
        }

        $isFilm = $type === WatchableType::Film;
        $title = (string) ($isFilm ? $data['title'] : $data['name']);
        $originalTitle = $isFilm ? ($data['original_title'] ?? null) : ($data['original_name'] ?? null);

        return [
            'tmdbId' => $tmdbId,
            'title' => $title,
            'originalTitle' => $originalTitle !== $title ? $originalTitle : null,
            'year' => $this->year($isFilm ? ($data['release_date'] ?? null) : ($data['first_air_date'] ?? null)),
            'creator' => $isFilm ? $this->director($data) : $this->createdBy($data),
            'genres' => array_values(array_map(fn (array $genre): string => (string) $genre['name'], $data['genres'] ?? [])),
            'runtimeMinutes' => $isFilm ? ($data['runtime'] ?? null ?: null) : (($data['episode_run_time'][0] ?? null) ?: null),
            'overview' => $overview !== '' ? $overview : null,
            'cast' => array_values(array_map(fn (array $member): array => [
                'name' => (string) $member['name'],
                'role' => (string) ($member['character'] ?? ''),
            ], array_slice($data['credits']['cast'] ?? [], 0, self::CAST_LIMIT))),
            'seasons' => $isFilm ? [] : array_values(array_map(fn (array $season): array => [
                'number' => (int) $season['season_number'],
                'episodeCount' => (int) ($season['episode_count'] ?? 0),
            ], array_filter($data['seasons'] ?? [], fn (array $season): bool => (int) $season['season_number'] > 0))),
            'posterPath' => $data['poster_path'] ?? null,
        ];
    }

    /**
     * Downloads a poster to a temporary file and returns its path.
     */
    public function downloadPoster(string $posterPath): string
    {
        $response = Http::timeout(20)->retry(2, 500)->get(self::IMAGE_URL.'w780'.$posterPath)->throw();
        $file = tempnam(sys_get_temp_dir(), 'poster');

        if ($file === false || file_put_contents($file, $response->body()) === false) {
            throw new RuntimeException('Could not write the downloaded poster.');
        }

        return $file;
    }

    private function request(): PendingRequest
    {
        return Http::baseUrl(self::BASE_URL)
            ->withToken((string) config('services.tmdb.token'))
            ->acceptJson()
            ->timeout(10)
            ->retry(2, 500, throw: false);
    }

    private function segment(WatchableType $type): string
    {
        return $type === WatchableType::Film ? 'movie' : 'tv';
    }

    private function year(?string $date): ?int
    {
        return filled($date) ? (int) substr((string) $date, 0, 4) : null;
    }

    /**
     * @param  array<string, mixed>  $data
     */
    private function director(array $data): ?string
    {
        $directors = array_filter($data['credits']['crew'] ?? [], fn (array $member): bool => ($member['job'] ?? null) === 'Director');

        return $directors !== [] ? implode(', ', array_map(fn (array $member): string => (string) $member['name'], array_slice(array_values($directors), 0, 2))) : null;
    }

    /**
     * @param  array<string, mixed>  $data
     */
    private function createdBy(array $data): ?string
    {
        $creators = $data['created_by'] ?? [];

        return $creators !== [] ? implode(', ', array_map(fn (array $member): string => (string) $member['name'], array_slice($creators, 0, 2))) : null;
    }
}
