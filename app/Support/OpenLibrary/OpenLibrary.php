<?php

namespace App\Support\OpenLibrary;

use Illuminate\Http\Client\PendingRequest;
use Illuminate\Support\Facades\Http;
use RuntimeException;

/**
 * Open Library (no API key), used only from the admin panel: search by title
 * or ISBN, then import the facts of one work. Site pages never call it;
 * everything is stored locally, the cover included.
 *
 * Search results are works, not editions: the publisher list of a work spans
 * every edition, so the publisher is left for Kadir to fill in. Turkish books
 * often come with odd spellings, every field stays editable.
 *
 * @phpstan-type SearchResult array{key: string, title: string, author: string, year: ?int, coverId: ?int, isbn: ?string, language: string}
 */
class OpenLibrary
{
    private const BASE_URL = 'https://openlibrary.org';

    private const COVER_URL = 'https://covers.openlibrary.org/b/id/';

    private const RESULT_LIMIT = 12;

    /** The book languages Kadir reads in, by their MARC code. */
    private const LANGUAGES = [
        'tur' => 'Türkçe',
        'eng' => 'İngilizce',
        'ger' => 'Almanca',
        'fre' => 'Fransızca',
        'spa' => 'İspanyolca',
        'ita' => 'İtalyanca',
        'por' => 'Portekizce',
        'rus' => 'Rusça',
    ];

    /**
     * Searches by ISBN when the query is one (hyphens and spaces allowed), by any words otherwise.
     *
     * @return list<SearchResult>
     */
    public function search(string $query): array
    {
        $isbn = self::isbn($query);

        $docs = $this->request()->get('/search.json', [
            ...($isbn !== null ? ['isbn' => $isbn] : ['q' => $query]),
            'fields' => 'key,title,author_name,first_publish_year,cover_i,language',
            'limit' => self::RESULT_LIMIT,
        ])->throw()->json('docs', []);

        return array_values(array_map(fn (array $doc): array => [
            'key' => (string) $doc['key'],
            'title' => (string) ($doc['title'] ?? ''),
            'author' => implode(', ', array_slice($doc['author_name'] ?? [], 0, 2)),
            'year' => isset($doc['first_publish_year']) ? (int) $doc['first_publish_year'] : null,
            'coverId' => isset($doc['cover_i']) ? (int) $doc['cover_i'] : null,
            'isbn' => $isbn,
            'language' => $this->language($doc['language'] ?? []),
        ], array_filter($docs, fn (array $doc): bool => isset($doc['key'], $doc['title']))));
    }

    /**
     * The description of a work, when Open Library has one.
     */
    public function description(string $workKey): ?string
    {
        $description = $this->request()->get($workKey.'.json')->json('description');
        $text = trim((string) (is_array($description) ? ($description['value'] ?? '') : $description));

        return $text !== '' ? $text : null;
    }

    /**
     * Downloads a cover to a temporary file and returns its path.
     */
    public function downloadCover(int $coverId): string
    {
        $response = Http::timeout(20)->retry(2, 500)->get(self::COVER_URL.$coverId.'-L.jpg')->throw();
        $file = tempnam(sys_get_temp_dir(), 'cover');

        if ($file === false || file_put_contents($file, $response->body()) === false) {
            throw new RuntimeException('Could not write the downloaded cover.');
        }

        return $file;
    }

    public static function coverUrl(int $coverId, string $size = 'M'): string
    {
        return self::COVER_URL.$coverId.'-'.$size.'.jpg';
    }

    /**
     * The ISBN-10 or ISBN-13 in the query (digits only, X allowed last), or null for a title search.
     */
    public static function isbn(string $query): ?string
    {
        $digits = strtoupper((string) preg_replace('/[\s-]/', '', $query));

        return preg_match('/^(\d{9}[\dX]|\d{13})$/', $digits) === 1 ? $digits : null;
    }

    /**
     * Turkish when the work has a Turkish edition (that is usually the one read), else its only known language.
     *
     * @param  list<string>  $codes
     */
    private function language(array $codes): string
    {
        if ($codes === [] || in_array('tur', $codes, true)) {
            return self::LANGUAGES['tur'];
        }

        return count($codes) === 1 ? (self::LANGUAGES[$codes[0]] ?? self::LANGUAGES['tur']) : self::LANGUAGES['tur'];
    }

    private function request(): PendingRequest
    {
        return Http::baseUrl(self::BASE_URL)
            ->withUserAgent('kadir.gulec.tr (+'.config('app.url').')')
            ->acceptJson()
            ->timeout(10)
            ->retry(2, 500, throw: false);
    }
}
