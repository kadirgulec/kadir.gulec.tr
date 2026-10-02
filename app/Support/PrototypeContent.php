<?php

namespace App\Support;

use Carbon\CarbonImmutable;

/**
 * Hard-coded sample content for the design prototype.
 * Each method mirrors what a real query will return later, so the views
 * and components can be wired to models without changing their shape.
 */
class PrototypeContent
{
    /**
     * @return array{title: string, slug: string, excerpt: string, publishedAt: CarbonImmutable, readingMinutes: int, tags: list<string>}
     */
    public static function latestPost(): array
    {
        return [
            'title' => 'Yapay zekâyla kod yazarken kendime koyduğum beş kural',
            'slug' => 'yapay-zekayla-kod-yazarken-bes-kural',
            'excerpt' => 'Asistan hızlı yazıyor, ama neyin doğru olduğuna hâlâ ben karar veriyorum. Bir yılın sonunda elimde kalan, biraz da acı tecrübeyle öğrendiğim beş kural.',
            'publishedAt' => CarbonImmutable::parse('2026-09-28'),
            'readingMinutes' => 6,
            'tags' => ['yapay zekâ', 'iş akışı'],
        ];
    }

    /**
     * @return array{title: string, slug: string, year: int, director: string, watchedAt: CarbonImmutable, rating: float, isFavorite: bool, hasReview: bool, posterUrl: ?string, posterColors: array{0: string, 1: string}}
     */
    public static function lastWatched(): array
    {
        return [
            'title' => 'Kuru Otlar Üstüne',
            'slug' => 'kuru-otlar-ustune',
            'year' => 2023,
            'director' => 'Nuri Bilge Ceylan',
            'watchedAt' => CarbonImmutable::parse('2026-09-30'),
            'rating' => 8.5,
            'isFavorite' => true,
            'hasReview' => true,
            'posterUrl' => null,
            'posterColors' => ['#c9b79c', '#5b4636'],
        ];
    }

    /**
     * @return list<array{title: string, slug: string, season: int, episode: int, episodeCount: int, posterColors: array{0: string, 1: string}}>
     */
    public static function currentlyWatching(): array
    {
        return [
            [
                'title' => 'Severance',
                'slug' => 'severance',
                'season' => 2,
                'episode' => 5,
                'episodeCount' => 10,
                'posterColors' => ['#9fb8c8', '#1f3a4d'],
            ],
            [
                'title' => 'The Bear',
                'slug' => 'the-bear',
                'season' => 3,
                'episode' => 2,
                'episodeCount' => 10,
                'posterColors' => ['#e8c27a', '#3b2a1c'],
            ],
        ];
    }

    /**
     * Daily chains with the last 14 days, oldest first.
     *
     * @return list<array{title: string, streak: int, days: list<'done'|'missed'|'excused'>}>
     */
    public static function activeChains(): array
    {
        return [
            [
                'title' => 'Her gün 30 dk kod',
                'streak' => 23,
                'days' => ['done', 'done', 'done', 'done', 'done', 'excused', 'done', 'done', 'done', 'done', 'done', 'done', 'done', 'done'],
            ],
            [
                'title' => 'Spor',
                'streak' => 6,
                'days' => ['done', 'done', 'missed', 'done', 'done', 'done', 'missed', 'done', 'done', 'done', 'done', 'done', 'done', 'done'],
            ],
            [
                'title' => 'Almanca okuma',
                'streak' => 2,
                'days' => ['done', 'missed', 'missed', 'done', 'done', 'done', 'done', 'done', 'missed', 'done', 'done', 'missed', 'done', 'done'],
            ],
        ];
    }

    /**
     * @return array{name: string, slug: string, tagline: string, status: 'in-progress'|'live'|'archived', imageUrl: string, demoUrl: string, stack: list<string>, latestLog: array{date: CarbonImmutable, text: string}}
     */
    public static function featuredProject(): array
    {
        return [
            'name' => 'CoMon',
            'slug' => 'comon',
            'tagline' => 'Sözleşmeleri, sayaç okumalarını ve ev bütçesini tek yerde tutan, çok kullanıcılı bir ev yönetimi uygulaması.',
            'status' => 'in-progress',
            'imageUrl' => '/images/projects/comon.webp',
            'demoUrl' => 'https://comon.guelec.eu',
            'stack' => ['Laravel', 'Livewire', 'Alpine.js', 'MySQL'],
            'latestLog' => [
                'date' => CarbonImmutable::parse('2026-09-24'),
                'text' => 'Sayaç okumalarına aylık tüketim grafiği eklendi.',
            ],
        ];
    }
}
