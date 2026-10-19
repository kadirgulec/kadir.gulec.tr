<?php

namespace App\Support\Seo;

use App\Enums\Section;
use App\Enums\WatchableType;
use App\Support\Content\BookContent;
use App\Support\Content\NoteContent;
use App\Support\Content\PostContent;
use App\Support\Content\WatchedContent;
use Carbon\CarbonInterface;

/**
 * schema.org data (JSON-LD) that tells search engines what a page is: the
 * site and its author on every page, the breadcrumb trail, and the page's own
 * node (a blog post, a note, a monthly review, a film or book review, the
 * about page).
 *
 * The site layout prints graph(); pages hand their own nodes to the layout.
 *
 * @phpstan-import-type PostData from PostContent
 * @phpstan-import-type NoteData from NoteContent
 * @phpstan-import-type WatchedEntry from WatchedContent
 * @phpstan-import-type BookEntry from BookContent
 */
class StructuredData
{
    /**
     * The JSON-LD of a page, ready for a <script type="application/ld+json">.
     *
     * @param  list<array<string, mixed>>  $nodes  the page's own nodes
     */
    public static function graph(Section $section, ?string $title, string $url, array $nodes = []): string
    {
        $graph = array_values(array_filter([
            self::website(),
            self::person(),
            self::breadcrumbs($section, $title, $url),
            ...$nodes,
        ]));

        // JSON_HEX_TAG: a "</script>" inside a title can never close the script element.
        return (string) json_encode(
            ['@context' => 'https://schema.org', '@graph' => $graph],
            JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE | JSON_HEX_TAG,
        );
    }

    /**
     * @return array<string, mixed>
     */
    public static function website(): array
    {
        return [
            '@type' => 'WebSite',
            '@id' => route('home').'#site',
            'url' => route('home'),
            'name' => config('about.person.name'),
            'alternateName' => 'kadir.gulec.tr',
            'inLanguage' => 'tr-TR',
            'publisher' => ['@id' => self::personId()],
        ];
    }

    /**
     * @return array<string, mixed>
     */
    public static function person(): array
    {
        return [
            '@type' => 'Person',
            '@id' => self::personId(),
            'name' => config('about.person.name'),
            'url' => route('about'),
            'jobTitle' => config('about.person.job_title'),
            'description' => config('about.person.description'),
            'worksFor' => ['@type' => 'Organization', 'name' => config('about.person.works_for')],
            'homeLocation' => ['@type' => 'Place', 'name' => config('about.person.home')],
            'sameAs' => config('about.person.same_as'),
        ];
    }

    /**
     * Ana Sayfa › section › page. None on the home page itself.
     *
     * @return array<string, mixed>|null
     */
    public static function breadcrumbs(Section $section, ?string $title, string $url): ?array
    {
        $crumbs = [['Ana Sayfa', route('home')]];

        if ($section !== Section::Home) {
            $crumbs[] = [$section->label(), $section->url()];
        }

        if (filled($title) && ! self::sameUrl($url, end($crumbs)[1])) {
            $crumbs[] = [$title, $url];
        }

        if (count($crumbs) < 2) {
            return null;
        }

        return [
            '@type' => 'BreadcrumbList',
            'itemListElement' => array_map(fn (array $crumb, int $index): array => [
                '@type' => 'ListItem',
                'position' => $index + 1,
                'name' => $crumb[0],
                'item' => $crumb[1],
            ], $crumbs, array_keys($crumbs)),
        ];
    }

    /**
     * @param  PostData  $post
     * @return array<string, mixed>
     */
    public static function blogPosting(array $post): array
    {
        return array_filter([
            '@type' => 'BlogPosting',
            '@id' => $post['url'].'#yazi',
            'mainEntityOfPage' => $post['url'],
            'url' => $post['url'],
            'headline' => $post['title'],
            'description' => $post['metaDescription'],
            'image' => $post['ogImage'],
            'datePublished' => $post['publishedAt']->toAtomString(),
            'dateModified' => $post['updatedAt']->toAtomString(),
            'author' => ['@id' => self::personId()],
            'publisher' => ['@id' => self::personId()],
            'inLanguage' => 'tr-TR',
            'keywords' => implode(', ', $post['tags']),
        ], filled(...));
    }

    /**
     * A note has no title, so its opening words are the headline. No rich
     * result exists for it; the node tells search engines it is a dated piece
     * by Kadir (Google forbids forum or social posting types for this).
     *
     * @param  NoteData  $note
     * @return array<string, mixed>
     */
    public static function notePosting(array $note, string $headline, string $image): array
    {
        return array_filter([
            '@type' => 'BlogPosting',
            '@id' => $note['url'].'#not',
            'mainEntityOfPage' => $note['url'],
            'url' => $note['url'],
            'headline' => $headline,
            'description' => $note['text'],
            'image' => $image,
            'datePublished' => $note['publishedAt']->toAtomString(),
            'author' => ['@id' => self::personId()],
            'publisher' => ['@id' => self::personId()],
            'inLanguage' => 'tr-TR',
            'keywords' => $note['tagName'],
        ], filled(...));
    }

    /**
     * A monthly review, as a dated piece by Kadir.
     *
     * @param  array{monthName: string, summary: ?string, url: string, ogImage: string, publishedAt: ?CarbonInterface, updatedAt: ?CarbonInterface}  $review
     * @return array<string, mixed>
     */
    public static function reviewPosting(array $review): array
    {
        return array_filter([
            '@type' => 'BlogPosting',
            '@id' => $review['url'].'#degerlendirme',
            'mainEntityOfPage' => $review['url'],
            'url' => $review['url'],
            'headline' => $review['monthName'].' değerlendirmesi',
            'description' => $review['summary'],
            'image' => $review['ogImage'],
            'datePublished' => $review['publishedAt']?->toAtomString(),
            'dateModified' => $review['updatedAt']?->toAtomString(),
            'author' => ['@id' => self::personId()],
            'publisher' => ['@id' => self::personId()],
            'inLanguage' => 'tr-TR',
        ], filled(...));
    }

    /**
     * Kadir's review of a film or series, only when it has his rating (out of 10).
     *
     * @param  WatchedEntry  $entry
     * @return array<string, mixed>|null
     */
    public static function review(array $entry): ?array
    {
        if ($entry['rating'] === null) {
            return null;
        }

        $isFilm = $entry['type'] === WatchableType::Film;

        return array_filter([
            '@type' => 'Review',
            '@id' => $entry['url'].'#degerlendirme',
            'url' => $entry['url'],
            'itemReviewed' => array_filter([
                '@type' => $isFilm ? 'Movie' : 'TVSeries',
                'name' => $entry['title'],
                'alternateName' => $entry['originalTitle'] !== $entry['title'] ? $entry['originalTitle'] : null,
                'image' => $entry['posterUrl'] ? url($entry['posterUrl']) : null,
                'dateCreated' => $entry['year'] ? (string) $entry['year'] : null,
                $isFilm ? 'director' : 'creator' => $entry['creator'] ? ['@type' => 'Person', 'name' => $entry['creator']] : null,
            ], filled(...)),
            'reviewRating' => [
                '@type' => 'Rating',
                'ratingValue' => $entry['rating'],
                'bestRating' => 10,
                'worstRating' => 0,
            ],
            'reviewBody' => $entry['reviewExcerpt'],
            'datePublished' => $entry['watchedAt']->toDateString(),
            'author' => ['@id' => self::personId()],
            'inLanguage' => 'tr-TR',
        ], filled(...));
    }

    /**
     * Kadir's review of a book, only when it has his rating (out of 10).
     *
     * @param  BookEntry  $entry
     * @return array<string, mixed>|null
     */
    public static function bookReview(array $entry): ?array
    {
        if ($entry['rating'] === null) {
            return null;
        }

        return array_filter([
            '@type' => 'Review',
            '@id' => $entry['url'].'#degerlendirme',
            'url' => $entry['url'],
            'itemReviewed' => array_filter([
                '@type' => 'Book',
                'name' => $entry['title'],
                'alternateName' => $entry['originalTitle'],
                'image' => $entry['coverUrl'] ? url($entry['coverUrl']) : null,
                'author' => array_map(fn (string $name): array => ['@type' => 'Person', 'name' => trim($name)], explode(',', $entry['author'])),
                'translator' => $entry['translator'] ? ['@type' => 'Person', 'name' => $entry['translator']] : null,
                'publisher' => $entry['publisher'] ? ['@type' => 'Organization', 'name' => $entry['publisher']] : null,
                'isbn' => $entry['isbn'],
                'dateCreated' => $entry['year'] ? (string) $entry['year'] : null,
            ], filled(...)),
            'reviewRating' => [
                '@type' => 'Rating',
                'ratingValue' => $entry['rating'],
                'bestRating' => 10,
                'worstRating' => 0,
            ],
            'reviewBody' => $entry['reviewExcerpt'],
            'datePublished' => $entry['readAt']?->toDateString(),
            'author' => ['@id' => self::personId()],
            'inLanguage' => 'tr-TR',
        ], filled(...));
    }

    /**
     * The about page is the author's profile.
     *
     * @return array<string, mixed>
     */
    public static function profilePage(): array
    {
        return [
            '@type' => 'ProfilePage',
            '@id' => route('about').'#sayfa',
            'url' => route('about'),
            'mainEntity' => ['@id' => self::personId()],
            'inLanguage' => 'tr-TR',
        ];
    }

    private static function personId(): string
    {
        return route('home').'#kadir';
    }

    private static function sameUrl(string $first, string $second): bool
    {
        return rtrim($first, '/') === rtrim($second, '/');
    }
}
