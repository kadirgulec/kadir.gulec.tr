<?php

use App\Support\UserAgentInfo;

it('reads the browser, system and device of real user agents', function (string $userAgent, string $browser, string $os, string $device) {
    $info = new UserAgentInfo($userAgent);

    expect([$info->browser(), $info->os(), $info->device(), $info->isBot()])->toBe([$browser, $os, $device, false]);
})->with([
    'Chrome on Windows' => ['Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/129.0.0.0 Safari/537.36', 'Chrome', 'Windows', 'desktop'],
    'Edge on Windows' => ['Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/129.0.0.0 Safari/537.36 Edg/129.0.0.0', 'Edge', 'Windows', 'desktop'],
    'Firefox on Linux' => ['Mozilla/5.0 (X11; Linux x86_64; rv:131.0) Gecko/20100101 Firefox/131.0', 'Firefox', 'Linux', 'desktop'],
    'Safari on macOS' => ['Mozilla/5.0 (Macintosh; Intel Mac OS X 14_6) AppleWebKit/605.1.15 (KHTML, like Gecko) Version/18.0 Safari/605.1.15', 'Safari', 'macOS', 'desktop'],
    'Safari on iPhone' => ['Mozilla/5.0 (iPhone; CPU iPhone OS 18_0 like Mac OS X) AppleWebKit/605.1.15 (KHTML, like Gecko) Version/18.0 Mobile/15E148 Safari/604.1', 'Safari', 'iOS', 'mobile'],
    'Safari on iPad' => ['Mozilla/5.0 (iPad; CPU OS 18_0 like Mac OS X) AppleWebKit/605.1.15 (KHTML, like Gecko) Version/18.0 Mobile/15E148 Safari/604.1', 'Safari', 'iOS', 'tablet'],
    'Samsung Internet on Android' => ['Mozilla/5.0 (Linux; Android 14; SM-S921B) AppleWebKit/537.36 (KHTML, like Gecko) SamsungBrowser/26.0 Chrome/122.0.0.0 Mobile Safari/537.36', 'Samsung Internet', 'Android', 'mobile'],
    'Chrome on an Android tablet' => ['Mozilla/5.0 (Linux; Android 14; SM-X710) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/129.0.0.0 Safari/537.36', 'Chrome', 'Android', 'tablet'],
]);

it('tells crawlers, scripts and empty user agents apart from people', function (string $userAgent) {
    expect((new UserAgentInfo($userAgent))->isBot())->toBeTrue();
})->with([
    'Googlebot' => ['Mozilla/5.0 (compatible; Googlebot/2.1; +http://www.google.com/bot.html)'],
    'headless Chrome' => ['Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.36 (KHTML, like Gecko) HeadlessChrome/129.0.0.0 Safari/537.36'],
    'curl' => ['curl/8.5.0'],
    'link preview' => ['facebookexternalhit/1.1 (+http://www.facebook.com/externalhit_uatext.php)'],
    'empty' => [''],
]);

it('falls back to "other" for an unknown browser and system', function () {
    $info = new UserAgentInfo('SomethingNew/1.0');

    expect([$info->browser(), $info->os()])->toBe([UserAgentInfo::OTHER, UserAgentInfo::OTHER]);
});
