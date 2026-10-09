<?php

use App\Models\PageView;
use App\Models\User;
use Livewire\Livewire;

it('shows visits, views and the ranked lists of the period', function () {
    $this->travelTo(now()->setTime(12, 0));
    PageView::factory()->count(2)->create(['visitor_hash' => 'aaaaaaaaaaaaaaaa', 'path' => '/yazilar/ilk-yazi', 'referrer_host' => 'mastodon.social']);
    PageView::factory()->create(['visitor_hash' => 'bbbbbbbbbbbbbbbb', 'path' => '/hakkimda', 'device' => 'mobile', 'browser' => 'Safari']);
    PageView::factory()->create(['visitor_hash' => 'cccccccccccccccc', 'path' => '/hakkimda', 'created_at' => now()->subDays(3)]);
    PageView::factory()->create(['path' => '/eski-sayfa', 'created_at' => now()->subDays(40)]);

    $this->actingAs(User::factory()->admin()->create());

    $page = Livewire::test('pages::admin.visitors');

    expect($page->get('totals'))->toBe(['visits' => 3, 'views' => 4, 'today' => 2]);
    $page->assertSeeInOrder(['/hakkimda', '/yazilar/ilk-yazi'])
        ->assertSee('mastodon.social')
        ->assertSee('Doğrudan ya da bilinmiyor')
        ->assertSee('Telefon')
        ->assertDontSee('/eski-sayfa');

    $page->set('days', 90)->assertSee('/eski-sayfa');
});

it('falls back to 30 days for a period that is not offered', function () {
    $this->actingAs(User::factory()->admin()->create());

    Livewire::test('pages::admin.visitors')
        ->set('days', 365)
        ->assertSet('days', 30);
});

it('shows an empty state when nobody visited', function () {
    $this->actingAs(User::factory()->admin()->create())
        ->get(route('admin.visitors.index'))
        ->assertOk()
        ->assertSee('Bu dönemde kayıtlı ziyaret yok');
});

it('hides the statistics from members', function () {
    $this->actingAs(User::factory()->member()->create())
        ->get(route('admin.visitors.index'))
        ->assertNotFound();
});
