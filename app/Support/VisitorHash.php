<?php

namespace App\Support;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Str;

/**
 * An anonymous visitor id for the visit statistics, without a cookie: a hash
 * of the IP address and the user agent, keyed with a random salt that lives
 * for one day. The salt is kept under a single cache key and overwritten on
 * the next day, so yesterday's hashes can no longer be recomputed and the
 * same person cannot be recognised across days. The IP itself is never stored.
 */
class VisitorHash
{
    private const CACHE_KEY = 'analytics.salt';

    public static function for(Request $request): string
    {
        $hash = hash_hmac('sha256', $request->ip().'|'.$request->userAgent(), self::todaysSalt());

        return substr($hash, 0, 16);
    }

    private static function todaysSalt(): string
    {
        $today = now()->toDateString();

        /** @var array{date: string, salt: string}|null $stored */
        $stored = Cache::get(self::CACHE_KEY);

        if ($stored === null || $stored['date'] !== $today) {
            $stored = ['date' => $today, 'salt' => Str::random(64)];
            Cache::forever(self::CACHE_KEY, $stored);
        }

        return $stored['salt'];
    }
}
