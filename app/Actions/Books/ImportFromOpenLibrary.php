<?php

namespace App\Actions\Books;

use App\Models\Book;
use App\Support\Images\PosterPalette;
use App\Support\OpenLibrary\OpenLibrary;
use Throwable;

/**
 * Creates a draft book from an Open Library search result: its facts, the
 * work's description when there is one, and the cover. A missing
 * description or cover never stops the import; Kadir fills them in by hand.
 *
 * @phpstan-import-type SearchResult from OpenLibrary
 */
class ImportFromOpenLibrary
{
    public function __construct(private OpenLibrary $openLibrary, private StoreCover $storeCover) {}

    /**
     * @param  SearchResult  $result
     */
    public function handle(array $result): Book
    {
        $existing = Book::query()->where('open_library_key', $result['key'])->first();

        if ($existing !== null) {
            return $existing;
        }

        $book = Book::query()->create([
            'open_library_key' => $result['key'],
            'isbn' => $result['isbn'],
            'title' => $result['title'],
            'author' => $result['author'] !== '' ? $result['author'] : 'Bilinmiyor',
            'year' => $result['year'],
            'language' => $result['language'],
            'overview' => $this->description($result['key']),
        ]);

        if ($result['coverId'] !== null) {
            try {
                $file = $this->openLibrary->downloadCover($result['coverId']);
                $this->storeCover->handle($book, $file);
                @unlink($file);

                return $book;
            } catch (Throwable $exception) {
                report($exception);
            }
        }

        $this->storeCover->useAccent($book, PosterPalette::FALLBACK_ACCENT);

        return $book;
    }

    private function description(string $workKey): ?string
    {
        try {
            return $this->openLibrary->description($workKey);
        } catch (Throwable $exception) {
            report($exception);

            return null;
        }
    }
}
