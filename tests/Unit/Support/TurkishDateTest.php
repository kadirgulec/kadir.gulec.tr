<?php

use App\Support\TurkishDate;
use Carbon\CarbonImmutable;

it('adds the locative suffix that matches the month', function (string $date, string $expected) {
    $phrase = TurkishDate::onDayMonth(CarbonImmutable::parse($date));

    expect($phrase)->toBe($expected);
})->with([
    'hard consonant, back vowel' => ['2026-03-08', "8 Mart'ta"],
    'soft consonant, back vowel' => ['2026-04-23', "23 Nisan'da"],
    'front vowel' => ['2026-09-30', "30 Eylül'de"],
    'front vowel, October' => ['2026-10-02', "2 Ekim'de"],
    'hard consonant, dotless i' => ['2026-12-31', "31 Aralık'ta"],
]);

it('puts a month alone into the locative', function (string $date, string $expected) {
    expect(TurkishDate::inMonth(CarbonImmutable::parse($date)))->toBe($expected);
})->with([
    'front vowel' => ['2026-10-01', "Ekim'de"],
    'soft consonant, back vowel' => ['2026-11-01', "Kasım'da"],
    'hard consonant' => ['2026-08-01', "Ağustos'ta"],
]);
