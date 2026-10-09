<?php

namespace App\Support;

use Carbon\CarbonInterface;

/**
 * Turkish date phrases that need grammar, not just translation.
 */
class TurkishDate
{
    /**
     * Locative suffix per month, following vowel harmony and consonant hardening
     * (Mart'ta, Nisan'da, Eylül'de, Aralık'ta).
     *
     * @var array<int, string>
     */
    private const MONTH_LOCATIVE_SUFFIXES = [
        1 => 'ta',  // Ocak
        2 => 'ta',  // Şubat
        3 => 'ta',  // Mart
        4 => 'da',  // Nisan
        5 => 'ta',  // Mayıs
        6 => 'da',  // Haziran
        7 => 'da',  // Temmuz
        8 => 'ta',  // Ağustos
        9 => 'de',  // Eylül
        10 => 'de', // Ekim
        11 => 'da', // Kasım
        12 => 'ta', // Aralık
    ];

    /**
     * "30 Eylül'de", for sentences like "30 Eylül'de izledim".
     */
    public static function onDayMonth(CarbonInterface $date): string
    {
        return $date->locale('tr')->translatedFormat('j F')."'".self::MONTH_LOCATIVE_SUFFIXES[$date->month];
    }

    /**
     * "Ekim".
     */
    public static function month(CarbonInterface $date): string
    {
        return $date->locale('tr')->translatedFormat('F');
    }

    /**
     * "Ekim 2026".
     */
    public static function monthYear(CarbonInterface $date): string
    {
        return $date->locale('tr')->translatedFormat('F Y');
    }

    /**
     * "Ekim'de", for "Ekim'de deneyeceğim".
     */
    public static function inMonth(CarbonInterface $date): string
    {
        return self::month($date)."'".self::MONTH_LOCATIVE_SUFFIXES[$date->month];
    }
}
