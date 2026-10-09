<?php

use App\Models\PageView;
use App\Models\User;

const FIREFOX_ON_LINUX = 'Mozilla/5.0 (X11; Linux x86_64; rv:131.0) Gecko/20100101 Firefox/131.0';

beforeEach(function () {
    $this->withoutDefer();
});

/**
 * @param  array<string, string>  $headers
 * @return array<string, string>
 */
function browserHeaders(array $headers = []): array
{
    return [...['User-Agent' => FIREFOX_ON_LINUX, 'Accept-Language' => 'tr-TR,tr;q=0.9'], ...$headers];
}

it('records a guest view of a public page without a cookie of its own', function () {
    $response = $this->withHeaders(browserHeaders())->get(route('privacy'));

    $response->assertOk();
    $cookieNames = array_map(fn ($cookie) => $cookie->getName(), $response->headers->getCookies());
    expect($cookieNames)->toEqualCanonicalizing(['XSRF-TOKEN', config('session.cookie')]);

    $view = PageView::query()->sole();
    expect($view->only(['path', 'referrer_host', 'utm_source', 'browser', 'os', 'device']))->toBe([
        'path' => '/gizlilik',
        'referrer_host' => null,
        'utm_source' => null,
        'browser' => 'Firefox',
        'os' => 'Linux',
        'device' => 'desktop',
    ])->and($view->visitor_hash)->toMatch('/^[0-9a-f]{16}$/');
});

it('keeps only the host of the referring site and the campaign source', function () {
    $this->withHeaders(browserHeaders(['Referer' => 'https://www.Google.com/search?q=kadir+g%C3%BCle%C3%A7']))
        ->get(route('privacy', ['utm_source' => 'Mastodon']));

    expect(PageView::query()->sole()->only(['referrer_host', 'utm_source', 'path']))->toBe([
        'referrer_host' => 'google.com',
        'utm_source' => 'mastodon',
        'path' => '/gizlilik',
    ]);
});

it('does not count a link from inside the site as a referrer', function () {
    $this->withHeaders(browserHeaders(['Referer' => route('home')]))->get(route('privacy'));

    expect(PageView::query()->sole()->referrer_host)->toBeNull();
});

it('gives the same visitor the same hash within a day and a new one the next day', function () {
    $this->travelTo(now()->setTime(10, 0));
    $this->withHeaders(browserHeaders())->get(route('privacy'));
    $this->withHeaders(browserHeaders())->get(route('imprint'));

    $this->travelTo(now()->addDay());
    $this->withHeaders(browserHeaders())->get(route('privacy'));

    [$first, $second, $nextDay] = PageView::query()->orderBy('id')->pluck('visitor_hash')->all();
    expect($second)->toBe($first)
        ->and($nextDay)->not->toBe($first);
});

it('skips views that should not be counted', function (array $headers) {
    $this->withHeaders(browserHeaders($headers))->get(route('privacy'))->assertOk();

    expect(PageView::query()->count())->toBe(0);
})->with([
    'a crawler' => [['User-Agent' => 'Mozilla/5.0 (compatible; Googlebot/2.1; +http://www.google.com/bot.html)']],
    'no Accept-Language, like most scripts' => [['Accept-Language' => '']],
    'Do Not Track' => [['DNT' => '1']],
    'Global Privacy Control' => [['Sec-GPC' => '1']],
    'a prefetch' => [['Sec-Purpose' => 'prefetch']],
]);

it('does not count the admins themselves', function () {
    $this->actingAs(User::factory()->admin()->create())
        ->withHeaders(browserHeaders())
        ->get(route('privacy'))
        ->assertOk();

    expect(PageView::query()->count())->toBe(0);
});

it('counts members like any other visitor', function () {
    $this->actingAs(User::factory()->member()->create())
        ->withHeaders(browserHeaders())
        ->get(route('privacy'));

    expect(PageView::query()->count())->toBe(1);
});

it('does not count missing pages or feeds', function () {
    $this->withHeaders(browserHeaders())->get('/yazilar/boyle-bir-yazi-yok')->assertNotFound();
    $this->withHeaders(browserHeaders())->get(route('posts.feed'))->assertOk();

    expect(PageView::query()->count())->toBe(0);
});

it('prunes views older than the retention period', function () {
    $old = PageView::factory()->create(['created_at' => now()->subMonths(PageView::KEEP_MONTHS)->subDay()]);
    $recent = PageView::factory()->create(['created_at' => now()->subMonths(PageView::KEEP_MONTHS)->addDay()]);

    $this->artisan('model:prune', ['--model' => [PageView::class]])->assertSuccessful();

    expect(PageView::query()->pluck('id')->all())->toBe([$recent->id])
        ->and($old->fresh())->toBeNull();
});
