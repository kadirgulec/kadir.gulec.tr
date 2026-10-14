<?php

namespace App\Support;

use Closure;

/**
 * Reading progress is always a percentage. What Kadir types can be "43",
 * "%43" or "43%" (the Kindle's figure), or "143/671" for a printed book,
 * which becomes the share of the pages read.
 */
class ReadingProgress
{
    /**
     * The percentage (0–100), or null when the input is empty or makes no sense.
     */
    public static function parse(string $input): ?int
    {
        $input = trim($input);

        if (preg_match('/^(\d+)\s*\/\s*(\d+)$/', $input, $pages) === 1) {
            [$read, $total] = [(int) $pages[1], (int) $pages[2]];

            return $total > 0 && $read <= $total ? (int) round($read / $total * 100) : null;
        }

        if (preg_match('/^%?\s*(\d{1,3})\s*%?$/', $input, $percent) === 1 && (int) $percent[1] <= 100) {
            return (int) $percent[1];
        }

        return null;
    }

    /**
     * A validation rule closure for a progress field.
     */
    public static function rule(): Closure
    {
        return function (string $attribute, mixed $value, Closure $fail): void {
            if (filled($value) && self::parse((string) $value) === null) {
                $fail('Yüzde (43 ya da %43) ya da sayfa (143/671) yaz.');
            }
        };
    }
}
