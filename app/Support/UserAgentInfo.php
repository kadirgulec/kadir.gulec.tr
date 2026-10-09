<?php

namespace App\Support;

/**
 * Reads the browser family, operating system and device type out of a
 * User-Agent header, coarse enough for the visit statistics, and tells
 * crawlers, scripts and monitoring tools apart from people.
 */
class UserAgentInfo
{
    private const BOT_PATTERN = '/bot|crawl|spider|slurp|curl|wget|python|java\/|go-http|axios|node-fetch|okhttp|httpclient|libwww|scrapy|headless|phantom|puppeteer|playwright|selenium|lighthouse|pagespeed|preview|monitor|uptime|facebookexternalhit|feed|rss|archiver/i';

    /**
     * Checked in order: Chromium-based browsers name Chrome and Safari too.
     *
     * @var array<string, string>
     */
    private const BROWSERS = [
        '/Edg(e|A|iOS)?\//' => 'Edge',
        '/OPR\/|Opera/' => 'Opera',
        '/SamsungBrowser/' => 'Samsung Internet',
        '/Firefox|FxiOS/' => 'Firefox',
        '/Chrome|CriOS|Chromium/' => 'Chrome',
        '/Safari/' => 'Safari',
    ];

    /**
     * Checked in order: Android names Linux, iOS names Mac OS X.
     *
     * @var array<string, string>
     */
    private const SYSTEMS = [
        '/Windows/' => 'Windows',
        '/Android/' => 'Android',
        '/iPhone|iPad|iPod/' => 'iOS',
        '/Macintosh|Mac OS X/' => 'macOS',
        '/CrOS/' => 'ChromeOS',
        '/Linux/' => 'Linux',
    ];

    public const OTHER = 'Diğer';

    public function __construct(private readonly string $userAgent) {}

    public function isBot(): bool
    {
        return trim($this->userAgent) === '' || preg_match(self::BOT_PATTERN, $this->userAgent) === 1;
    }

    public function browser(): string
    {
        return $this->firstMatch(self::BROWSERS);
    }

    public function os(): string
    {
        return $this->firstMatch(self::SYSTEMS);
    }

    /**
     * One of "desktop", "mobile" or "tablet".
     */
    public function device(): string
    {
        if (preg_match('/iPad|Tablet/i', $this->userAgent) === 1
            || (str_contains($this->userAgent, 'Android') && ! str_contains($this->userAgent, 'Mobile'))) {
            return 'tablet';
        }

        return preg_match('/Mobi|iPhone|iPod|Android/', $this->userAgent) === 1 ? 'mobile' : 'desktop';
    }

    /**
     * @param  array<string, string>  $patterns
     */
    private function firstMatch(array $patterns): string
    {
        foreach ($patterns as $pattern => $name) {
            if (preg_match($pattern, $this->userAgent) === 1) {
                return $name;
            }
        }

        return self::OTHER;
    }
}
