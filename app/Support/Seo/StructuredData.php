<?php

namespace App\Support\Seo;

use App\Enums\Section;
use App\Enums\WatchableType;
use App\Support\Content\PostContent;
use App\Support\Content\WatchedContent;

/**
 * schema.org data (JSON-LD) that tells search engines what a page is: the
 * site and its author on every page, the breadcrumb trail, and the page's own
 * node (a blog post, a film review, the about page).
 *
 * The site layout prints graph(); pages hand their own nodes to the layout.
 *
 * @phpstan-import-type PostData from PostContent
 * @phpstan-import-type WatchedEntry from WatchedContent
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
            'name' => 'kadir.gulec.tr',
            'alternateName' => config('about.person.name'),
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
