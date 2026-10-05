<?php

/*
 * Sample watchlist ("Sırada") for local development only (DemoSeeder), in list order.
 * Not watched yet: drafts without viewings, ratings or posters. Titles, years and creators
 * are as on TMDB; the overviews are short summaries and the notes are sample data.
 */

return [
    [
        'type' => 'series',
        'slug' => 'mad-men',
        'title' => 'Mad Men',
        'original_title' => null,
        'year' => 2007,
        'creator' => 'Matthew Weiner',
        'genres' => ['Dram'],
        'overview' => '1960\'ların New York\'unda, Madison Avenue\'daki bir reklam ajansında çalışan gizemli yaratıcı direktör Don Draper ve çevresindekiler.',
        'poster_colors' => ['#c9a66b', '#3b2a1a'],
        'accent' => '#e3c48f',
        'note' => 'Üç bölüm izledim, devam edip etmeyeceğime karar veremedim.',
    ],
    [
        'type' => 'series',
        'slug' => 'dark',
        'title' => 'Dark',
        'original_title' => null,
        'year' => 2017,
        'creator' => 'Baran bo Odar, Jantje Friese',
        'genres' => ['Gizem', 'Dram'],
        'overview' => 'Küçük bir Alman kasabasında iki çocuğun ortadan kaybolması, dört ailenin geçmişindeki sırları ve zamanın kendisini gün yüzüne çıkarır.',
        'poster_colors' => ['#5b6b73', '#121a1f'],
        'accent' => '#8fa3ad',
        'note' => 'Herkes öneriyor, Almancam da gelişir.',
    ],
    [
        'type' => 'film',
        'slug' => 'blade-runner-2049',
        'title' => 'Blade Runner 2049',
        'original_title' => null,
        'year' => 2017,
        'creator' => 'Denis Villeneuve',
        'genres' => ['Bilim-Kurgu', 'Dram'],
        'overview' => 'Genç bir "blade runner" olan K, uzun süredir gömülü kalmış bir sırrı ortaya çıkarınca, otuz yıldır kayıp olan eski blade runner Rick Deckard\'ın izini sürmeye başlar.',
        'poster_colors' => ['#d98a3d', '#2a1a2e'],
        'accent' => '#f0b070',
        'note' => 'Dune\'dan sonra sıradaki Villeneuve.',
    ],
    [
        'type' => 'film',
        'slug' => 'ahlat-agaci',
        'title' => 'Ahlat Ağacı',
        'original_title' => null,
        'year' => 2018,
        'creator' => 'Nuri Bilge Ceylan',
        'genres' => ['Dram'],
        'overview' => 'Üniversiteden mezun olup Çanakkale\'deki köyüne dönen Sinan, yazdığı kitabı bastırmak için para ararken borçlarla boğuşan babasıyla yüzleşir.',
        'poster_colors' => ['#a8b07a', '#2e3320'],
        'accent' => '#c8cf9a',
        'note' => 'Kuru Otlar Üstüne\'den sonra sıradaki Ceylan.',
    ],
    [
        'type' => 'series',
        'slug' => 'better-call-saul',
        'title' => 'Better Call Saul',
        'original_title' => null,
        'year' => 2015,
        'creator' => 'Vince Gilligan, Peter Gould',
        'genres' => ['Dram', 'Suç'],
        'overview' => 'Breaking Bad\'deki avukat Saul Goodman\'ın, daha Jimmy McGill olduğu yıllarda küçük işlerle geçinen bir avukattan nasıl dönüştüğünün hikâyesi.',
        'poster_colors' => ['#e0b84a', '#3a2c12'],
        'accent' => '#f2d27a',
        'note' => null,
    ],
];
