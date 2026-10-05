<?php

use App\Models\User;
use App\Models\Watchable;
use Illuminate\Support\Facades\Http;

/*
 * Every item of the open menu is on top at its own spot, so nothing is clipped
 * or covered.
 */
const MENU_ITEMS_ON_TOP = <<<'JS'
    [...document.querySelector('[role=menu]:not([style*="display: none"])').querySelectorAll('a, button')].every(item => {
        const box = item.getBoundingClientRect();
        return item.contains(document.elementFromPoint(box.x + box.width / 2, box.y + box.height / 2));
    })
JS;

beforeEach(function () {
    $this->actingAs(User::factory()->admin()->create());
});

it('shows the whole row menu when the watchlist has a single entry', function () {
    Watchable::factory()->create(['title' => 'Dune'])->addToWatchlist();

    visit(route('admin.watched.watchlist'))
        ->click('button[aria-label="Dune: işlemler"]')
        ->assertSee('Listeden çıkar')
        ->assertScript(MENU_ITEMS_ON_TOP, true);
});

it('opens the help tooltip that sits inside a paragraph on the TMDB results', function () {
    config(['services.tmdb.token' => 'test-token']);
    Http::fake(['api.themoviedb.org/3/search/*' => Http::response(['results' => [
        ['id' => 438631, 'title' => 'Dune', 'original_title' => 'Dune', 'release_date' => '2021-09-15', 'poster_path' => null, 'overview' => 'Çöl gezegeni.'],
    ]])]);

    visit(route('admin.watched.create'))
        ->type('input[aria-label="TMDB\'de ara"]', 'Dune')
        ->press('Ara')
        ->assertSee('Her sonuç için iki seçenek')
        ->click('button[aria-label="İki seçeneğin farkı"]')
        ->assertVisible('[role=tooltip]')
        ->assertSee('Bilgileri ve afişi kopyalar')
        // Still inside its x-data wrapper, so it is anchored to the ⓘ and hover closes it.
        ->assertScript('document.querySelector(\'button[aria-label="İki seçeneğin farkı"]\').parentElement.contains(document.querySelector("[role=tooltip]"))', true);
});
