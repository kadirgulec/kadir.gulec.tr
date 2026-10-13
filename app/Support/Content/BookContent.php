<?php

namespace App\Support\Content;

use App\Enums\BookFormat;
use App\Enums\ReadingStatus;
use App\Support\Markdown\Markdown;
use Carbon\CarbonImmutable;
use Illuminate\Support\HtmlString;
use Illuminate\Support\Str;

/**
 * Books and readings in the shape the site views expect. A diary entry is one
 * finished or abandoned reading (with its book's facts); the detail page is one book.
 *
 * Prototype (RAFIM.md, step 2): the books are fixed demo data until the model
 * exists (step 3). The array shapes are the contract the views keep afterwards.
 *
 * @phpstan-type Quote array{text: string, by: ?string, location: ?string}
 * @phpstan-type Reading array{startedOn: CarbonImmutable, finishedOn: ?CarbonImmutable, status: ReadingStatus, progress: ?int}
 * @phpstan-type BookEntry array{
 *     id: int, slug: string, title: string, originalTitle: ?string, author: string, translator: ?string, publisher: ?string,
 *     year: ?int, language: string, format: BookFormat, overview: ?string,
 *     coverUrl: ?string, coverColors: array{0: string, 1: string}, accent: string,
 *     rating: ?float, isFavorite: bool, status: ReadingStatus, progress: ?int,
 *     startedOn: CarbonImmutable, finishedOn: ?CarbonImmutable, readAt: CarbonImmutable, isReread: bool, readings: list<Reading>,
 *     hasReview: bool, reviewHtml: ?HtmlString, reviewExcerpt: ?string, quotes: list<Quote>,
 *     url: string, isDraft: bool, metaDescription: string
 * }
 */
class BookContent
{
    public function __construct(private Markdown $markdown) {}

    /**
     * Every finished or abandoned reading, newest first.
     *
     * @return list<BookEntry>
     */
    public function diary(): array
    {
        $entries = [];

        foreach (self::books() as $book) {
            foreach ($book['readings'] as $index => $reading) {
                if (! $reading['status']->isInProgress()) {
                    $entries[] = $this->toArray($book, $index);
                }
            }
        }

        return $this->newestFirst($entries);
    }

    /**
     * The latest finished or abandoned readings, one per book, newest first.
     *
     * @return list<BookEntry>
     */
    public function recent(int $limit): array
    {
        return array_slice(array_values(collect($this->diary())->unique('id')->all()), 0, $limit);
    }

    /**
     * Books being read or paused, most recently started first.
     *
     * @return list<BookEntry>
     */
    public function currentlyReading(): array
    {
        $entries = [];

        foreach (self::books() as $book) {
            if ($book['readings'][0]['status']->isInProgress()) {
                $entries[] = $this->toArray($book, 0);
            }
        }

        return $this->newestFirst($entries);
    }

    /**
     * The reading list ("okuyacaklarım"), in Kadir's order. Not read yet, so no links.
     *
     * @return list<array{title: string, author: string, coverColors: array{0: string, 1: string}, note: ?string}>
     */
    public function toRead(): array
    {
        return [
            ['title' => 'Huzur', 'author' => 'Ahmet Hamdi Tanpınar', 'coverColors' => ['#1f3b4d', '#7fb2c9'], 'note' => 'Saatleri bitirince'],
            ['title' => 'Dune', 'author' => 'Frank Herbert', 'coverColors' => ['#5a3412', '#e8a04c'], 'note' => 'Ali önerdi'],
            ['title' => 'Kafamda Bir Tuhaflık', 'author' => 'Orhan Pamuk', 'coverColors' => ['#3d2a4f', '#c9a0dc'], 'note' => null],
            ['title' => 'Thinking, Fast and Slow', 'author' => 'Daniel Kahneman', 'coverColors' => ['#20323a', '#e2c044'], 'note' => 'iş yerinde önerdiler'],
            ['title' => 'Der Steppenwolf', 'author' => 'Hermann Hesse', 'coverColors' => ['#2e3b2c', '#9fbf7a'], 'note' => 'Almanca okumak için'],
        ];
    }

    /**
     * How many readings were finished in a year (abandoned ones do not count).
     */
    public function finishedCountIn(int $year): int
    {
        return collect($this->diary())->filter(fn (array $entry): bool => $entry['status'] === ReadingStatus::Finished && $entry['readAt']->year === $year)->count();
    }

    /**
     * @return BookEntry|null
     */
    public function find(string $slug): ?array
    {
        $book = collect(self::books())->firstWhere('slug', $slug);

        return $book ? $this->toArray($book, 0) : null;
    }

    /**
     * One book, seen through one of its readings (0 is the latest).
     *
     * @param  array<string, mixed>  $book
     * @return BookEntry
     */
    private function toArray(array $book, int $readingIndex): array
    {
        /** @var list<Reading> $readings */
        $readings = $book['readings'];
        $reading = $readings[$readingIndex];
        $review = $book['review'];

        return [
            'id' => $book['id'],
            'slug' => $book['slug'],
            'title' => $book['title'],
            'originalTitle' => $book['originalTitle'],
            'author' => $book['author'],
            'translator' => $book['translator'],
            'publisher' => $book['publisher'],
            'year' => $book['year'],
            'language' => $book['language'],
            'format' => $book['format'],
            'overview' => $book['overview'],
            'coverUrl' => null,
            'coverColors' => $book['coverColors'],
            'accent' => $book['coverColors'][1],
            'rating' => $book['rating'],
            'isFavorite' => $book['isFavorite'],
            'status' => $reading['status'],
            'progress' => $reading['progress'],
            'startedOn' => $reading['startedOn'],
            'finishedOn' => $reading['finishedOn'],
            'readAt' => $reading['finishedOn'] ?? $reading['startedOn'],
            'isReread' => $readingIndex < count($readings) - 1,
            'readings' => $readings,
            'hasReview' => $review !== null,
            'reviewHtml' => $review !== null ? new HtmlString($this->markdown->toHtml($review)) : null,
            'reviewExcerpt' => $review !== null ? $this->markdown->excerpt((string) preg_replace('/^:::[ \t]*(spoiler|alıntı).*?^:::[ \t]*$/msu', '', $review), 180) : null,
            'quotes' => $book['quotes'],
            'url' => route('books.show', $book['slug']),
            'isDraft' => false,
            'metaDescription' => Str::limit((string) $book['overview'], 155, '…', preserveWords: true),
        ];
    }

    /**
     * @param  list<BookEntry>  $entries
     * @return list<BookEntry>
     */
    private function newestFirst(array $entries): array
    {
        usort($entries, fn (array $a, array $b): int => $b['readAt'] <=> $a['readAt']);

        return $entries;
    }

    /**
     * The demo books. Readings are newest first; reviews and quotes are placeholders.
     *
     * @return list<array<string, mixed>>
     */
    private static function books(): array
    {
        $day = fn (string $date): CarbonImmutable => CarbonImmutable::parse($date);
        $read = fn (string $started, ?string $finished, ReadingStatus $status = ReadingStatus::Finished, ?int $progress = null): array => [
            'startedOn' => $day($started),
            'finishedOn' => $finished !== null ? $day($finished) : null,
            'status' => $status,
            'progress' => $progress,
        ];
        $placeholderQuote = fn (?string $by, ?string $location): array => [
            'text' => 'Burada kitaptan altını çizdiğim bir cümle duracak. Prototip için yazılmış bir yer tutucu.',
            'by' => $by,
            'location' => $location,
        ];

        return [
            [
                'id' => 1, 'slug' => 'saatleri-ayarlama-enstitusu', 'title' => 'Saatleri Ayarlama Enstitüsü', 'originalTitle' => null,
                'author' => 'Ahmet Hamdi Tanpınar', 'translator' => null, 'publisher' => 'Dergâh Yayınları', 'year' => 1961,
                'language' => 'Türkçe', 'format' => BookFormat::Ebook, 'coverColors' => ['#3b2f1e', '#d9a441'],
                'overview' => 'Hayri İrdal\'ın gözünden, saatleri ayarlamak için kurulan bir enstitünün ve bir dönemin hikâyesi.',
                'rating' => null, 'isFavorite' => false, 'review' => null, 'quotes' => [$placeholderQuote('Hayri İrdal', '%18')],
                'readings' => [$read('2026-09-28', null, ReadingStatus::Reading, 43)],
            ],
            [
                'id' => 2, 'slug' => 'clean-code', 'title' => 'Clean Code', 'originalTitle' => null,
                'author' => 'Robert C. Martin', 'translator' => null, 'publisher' => 'Prentice Hall', 'year' => 2008,
                'language' => 'İngilizce', 'format' => BookFormat::Print, 'coverColors' => ['#1d2b3a', '#5fa8d3'],
                'overview' => 'Okunabilir, değiştirilebilir kod yazmak üzerine örneklerle dolu bir el kitabı.',
                'rating' => null, 'isFavorite' => false, 'review' => null, 'quotes' => [],
                'readings' => [$read('2026-06-02', null, ReadingStatus::Paused, 71)],
            ],
            [
                'id' => 3, 'slug' => 'kurk-mantolu-madonna', 'title' => 'Kürk Mantolu Madonna', 'originalTitle' => null,
                'author' => 'Sabahattin Ali', 'translator' => null, 'publisher' => 'Yapı Kredi Yayınları', 'year' => 1943,
                'language' => 'Türkçe', 'format' => BookFormat::Ebook, 'coverColors' => ['#4a1f2b', '#d9707f'],
                'overview' => 'Raif Efendi\'nin defterinde saklı kalan, Berlin\'de başlayan bir aşk hikâyesi.',
                'rating' => 9.0, 'isFavorite' => true,
                'review' => "Bu, prototip için yazılmış örnek bir yorum. Gerçek yorumu Kadir yazacak; burada sadece sayfanın nasıl göründüğü deneniyor.\n\nİkinci paragraf: kitabın bende bıraktığı his, hangi sahnede durup düşündüğüm, kime önereceğim gibi şeyler burada olur.\n\n:::alıntı Raif Efendi\nYorumun akışında geçen bir alıntı burada durur. Yer tutucu.\n:::\n\n:::spoiler\nSonla ilgili bir düşünce, markörle kapalı.\n:::",
                'quotes' => [$placeholderQuote('Raif Efendi', '%34'), $placeholderQuote(null, '%61'), $placeholderQuote('Maria Puder', '%88')],
                'readings' => [$read('2026-09-03', '2026-09-21')],
            ],
            [
                'id' => 4, 'slug' => 'korluk', 'title' => 'Körlük', 'originalTitle' => 'Ensaio sobre a Cegueira',
                'author' => 'José Saramago', 'translator' => 'Aykut Derman', 'publisher' => 'Kırmızı Kedi', 'year' => 1995,
                'language' => 'Türkçe', 'format' => BookFormat::Ebook, 'coverColors' => ['#2b2b2b', '#bdbdbd'],
                'overview' => 'Bir şehirde insanlar birer birer kör olmaya başlayınca toplumun dokusu da çözülmeye başlar.',
                'rating' => 8.5, 'isFavorite' => false,
                'review' => "Örnek yorum, ilk paragraf. Bu metin prototip için bir yer tutucu.\n\nUzunca bir ikinci paragraf, diary kartında sadece ilk iki satırın göründüğünü denemek için.",
                'quotes' => [$placeholderQuote('Doktorun karısı', '%52')],
                'readings' => [$read('2026-07-20', '2026-08-10')],
            ],
            [
                'id' => 5, 'slug' => 'atomic-habits', 'title' => 'Atomic Habits', 'originalTitle' => null,
                'author' => 'James Clear', 'translator' => null, 'publisher' => 'Avery', 'year' => 2018,
                'language' => 'İngilizce', 'format' => BookFormat::Audio, 'coverColors' => ['#f2efe6', '#e0a526'],
                'overview' => 'Küçük alışkanlıkların zamanla nasıl büyük sonuçlara dönüştüğü üzerine.',
                'rating' => 6.5, 'isFavorite' => false, 'review' => null, 'quotes' => [],
                'readings' => [$read('2026-06-10', '2026-07-02')],
            ],
            [
                'id' => 6, 'slug' => 'die-verwandlung', 'title' => 'Die Verwandlung', 'originalTitle' => null,
                'author' => 'Franz Kafka', 'translator' => null, 'publisher' => 'Reclam', 'year' => 1915,
                'language' => 'Almanca', 'format' => BookFormat::Ebook, 'coverColors' => ['#2f3d1f', '#a8c256'],
                'overview' => 'Gregor Samsa bir sabah uyandığında kendini dev bir böceğe dönüşmüş bulur.',
                'rating' => 7.5, 'isFavorite' => false, 'review' => null, 'quotes' => [],
                'readings' => [$read('2026-05-04', '2026-05-15')],
            ],
            [
                'id' => 7, 'slug' => 'simyaci', 'title' => 'Simyacı', 'originalTitle' => 'O Alquimista',
                'author' => 'Paulo Coelho', 'translator' => null, 'publisher' => 'Can Yayınları', 'year' => 1988,
                'language' => 'Türkçe', 'format' => BookFormat::Ebook, 'coverColors' => ['#5c3b10', '#f0c674'],
                'overview' => 'Endülüslü bir çobanın hazine peşinde çıktığı yolculuk.',
                'rating' => null, 'isFavorite' => false, 'review' => null, 'quotes' => [],
                'readings' => [$read('2026-03-20', '2026-04-02', ReadingStatus::Abandoned, 38)],
            ],
            [
                'id' => 8, 'slug' => 'tutunamayanlar', 'title' => 'Tutunamayanlar', 'originalTitle' => null,
                'author' => 'Oğuz Atay', 'translator' => null, 'publisher' => 'İletişim Yayınları', 'year' => 1972,
                'language' => 'Türkçe', 'format' => BookFormat::Ebook, 'coverColors' => ['#1e2a44', '#e85d4a'],
                'overview' => 'Selim Işık\'ın intiharının ardından arkadaşı Turgut Özben\'in onun izini sürdüğü roman.',
                'rating' => 10.0, 'isFavorite' => true,
                'review' => 'İkinci okuma üzerine örnek bir yorum. Prototip için yer tutucu metin.',
                'quotes' => [$placeholderQuote('Selim Işık', '3. bölüm'), $placeholderQuote('Turgut Özben', '%72')],
                'readings' => [$read('2025-12-01', '2026-01-20'), $read('2019-02-10', '2019-04-28')],
            ],
            [
                'id' => 9, 'slug' => 'tehlikeli-oyunlar', 'title' => 'Tehlikeli Oyunlar', 'originalTitle' => null,
                'author' => 'Oğuz Atay', 'translator' => null, 'publisher' => 'İletişim Yayınları', 'year' => 1973,
                'language' => 'Türkçe', 'format' => BookFormat::Print, 'coverColors' => ['#3a1f3d', '#d77fa1'],
                'overview' => 'Hikmet Benol\'un gecekondusunda kurduğu oyunlar ve gerçeklik arasında gidip gelen bir roman.',
                'rating' => 8.0, 'isFavorite' => false, 'review' => null, 'quotes' => [],
                'readings' => [$read('2025-10-15', '2025-11-30')],
            ],
        ];
    }
}
