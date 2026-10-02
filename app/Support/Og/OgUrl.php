<?php

namespace App\Support\Og;

use Carbon\CarbonInterface;

/**
 * Addresses of link preview images. The version (the row's update time)
 * changes the address when the content changes, so caches never show an
 * old picture.
 */
class OgUrl
{
    public static function for(string $kind, string $key, ?CarbonInterface $version = null): string
    {
        return route('og', ['kind' => $kind, 'key' => $key, 'v' => $version?->getTimestamp() ?? 0]);
    }
}
