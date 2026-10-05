<?php

use App\Models\User;
use App\Models\Watchable;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Storage;
use Livewire\Livewire;

beforeEach(function () {
    Storage::fake('public');
    config(['services.tmdb.token' => 'test-token']);
    $this->actingAs(User::factory()->admin()->create());
});

/**
 * @return list<string>
 */
function watchlistTitles(): array
{
    return Watchable::query()->onWatchlist()->pluck('title')->all();
}

it('keeps members out of the watchlist screen', function () {
    $this->actingAs(User::factory()->member()->create())
        ->get(route('admin.watched.watchlist'))
        ->assertNotFound();
});

it('imports a TMDB result straight onto the end of the watchlist', function () {
    Http::fake([
        'api.themoviedb.org/3/tv/48866*' => Http::response([
            'name' => 'The 100', 'original_name' => 'The 100', 'first_air_date' => '2014-03-19', 'overview' => 'Dünya\'ya dönüş.',
            'created_by' => [['name' => 'Jason Rothenberg']], 'genres' => [], 'episode_run_time' => [43], 'poster_path' => '/p.jpg',
            'credits' => ['cast' => [], 'crew' => []], 'seasons' => [['season_number' => 1, 'episode_count' => 13]],
        ]),
        'image.tmdb.org/*' => Http::response(UploadedFile::fake()->image('p.jpg', 300, 450)->getContent(), 200, ['Content-Type' => 'image/jpeg']),
    ]);
    Watchable::factory()->create(['title' => 'Önceki'])->addToWatchlist();

    Livewire::test('pages::admin.watched.create')
        ->set('type', 'series')
        ->call('addToWatchlist', 48866)
        ->assertRedirect(route('admin.watched.watchlist'));

    expect(watchlistTitles())->toBe(['Önceki', 'The 100'])
        ->and(Watchable::query()->where('title', 'The 100')->sole()->published_at)->toBeNull();
});

it('puts an entry that already exists on the watchlist instead of importing it twice', function () {
    $existing = Watchable::factory()->series()->create(['tmdb_id' => 48866]);

    Livewire::test('pages::admin.watched.create')
        ->set('type', 'series')
        ->call('addToWatchlist', 48866);

    expect(Watchable::query()->count())->toBe(1)
        ->and($existing->fresh()->isOnWatchlist())->toBeTrue();
});

it('reorders the watchlist, keeps a note and takes entries off', function () {
    foreach (['Dune', 'Mad Men', 'Dark'] as $title) {
        Watchable::factory()->create(['title' => $title])->addToWatchlist();
    }
    $dark = Watchable::query()->where('title', 'Dark')->sole();

    $page = Livewire::test('pages::admin.watched.watchlist')
        ->assertSeeInOrder(['Dune', 'Mad Men', 'Dark'])
        ->call('sort', $dark->id, 0);
    expect(watchlistTitles())->toBe(['Dark', 'Dune', 'Mad Men']);

    $page->call('editNote', $dark->id)->set('note', ' Ayşe önerdi ')->call('saveNote')->assertHasNoErrors();
    expect($dark->fresh()->watchlist_note)->toBe('Ayşe önerdi');

    $page->call('remove', $dark->id);
    expect(watchlistTitles())->toBe(['Dune', 'Mad Men'])
        ->and($dark->fresh()->watchlist_note)->toBeNull();
});

it('takes an entry off the watchlist with its first viewing', function () {
    $film = Watchable::factory()->create();
    $film->addToWatchlist('Ayşe önerdi');

    Livewire::test('pages::admin.watched.edit', ['watchable' => $film])
        ->set('viewingDate', now()->toDateString())
        ->call('addViewing')
        ->assertHasNoErrors();

    expect($film->fresh()->isOnWatchlist())->toBeFalse();
});

it('adds and removes an entry from its edit screen', function () {
    $film = Watchable::factory()->create();

    $page = Livewire::test('pages::admin.watched.edit', ['watchable' => $film])->call('addToWatchlist');
    expect($film->fresh()->isOnWatchlist())->toBeTrue();

    $page->call('removeFromWatchlist');
    expect($film->fresh()->isOnWatchlist())->toBeFalse();
});

it('shows the watchlist as "Sırada" on the watched page, without links to unwatched drafts', function () {
    $draft = Watchable::factory()->draft()->create(['title' => 'Mad Men']);
    $draft->addToWatchlist('Üç bölüm sonra karar vereceğim');

    $this->get(route('watched.index'))
        ->assertOk()
        ->assertSeeText('Sırada')
        ->assertSeeText('Mad Men')
        ->assertSeeText('Üç bölüm sonra karar vereceğim')
        ->assertDontSee('href="'.url($draft->publicPath()).'"', escape: false);
});

it('shows the first six on the watched page and the rest behind "hepsini göster"', function () {
    foreach (range(1, 8) as $i) {
        Watchable::factory()->draft()->create(['title' => "Sıradaki {$i}"])->addToWatchlist();
    }

    $html = $this->get(route('watched.index'))->assertOk()->assertSeeText('hepsini göster (2 tane daha)')->getContent();

    expect(strpos($html, 'Sıradaki 6'))->toBeLessThan(strpos($html, '<details'))
        ->and(strpos($html, 'Sıradaki 7'))->toBeGreaterThan(strpos($html, '<details'));
});

it('has no "hepsini göster" while six or fewer are on the watchlist', function () {
    foreach (range(1, 6) as $i) {
        Watchable::factory()->draft()->create(['title' => "Sıradaki {$i}"])->addToWatchlist();
    }

    $this->get(route('watched.index'))->assertOk()->assertSeeText('Sıradaki 6')->assertDontSeeText('hepsini göster');
});

it('leaves the "Sırada" section out while the watchlist is empty', function () {
    $this->get(route('watched.index'))->assertOk()->assertDontSeeText('izleyeceklerim, sırasıyla');
});
