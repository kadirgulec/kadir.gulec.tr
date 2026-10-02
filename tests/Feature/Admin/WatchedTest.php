<?php

use App\Enums\SeriesStatus;
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
 * TMDB answers for one series (Severance) and its poster.
 */
function fakeTmdb(): void
{
    $poster = UploadedFile::fake()->image('poster.jpg', 300, 450)->getContent();

    Http::fake([
        'api.themoviedb.org/3/search/tv*' => Http::response(['results' => [
            ['id' => 95396, 'name' => 'Severance', 'original_name' => 'Severance', 'first_air_date' => '2022-02-17', 'poster_path' => '/p.jpg', 'overview' => 'Ofiste gizem.'],
        ]]),
        'api.themoviedb.org/3/tv/95396*' => Http::response([
            'name' => 'Severance', 'original_name' => 'Severance', 'first_air_date' => '2022-02-17', 'overview' => 'Ofiste gizem.',
            'created_by' => [['name' => 'Dan Erickson']], 'genres' => [['name' => 'Dram'], ['name' => 'Gizem']],
            'episode_run_time' => [50], 'poster_path' => '/p.jpg',
            'credits' => ['cast' => [['name' => 'Adam Scott', 'character' => 'Mark Scout']], 'crew' => []],
            'seasons' => [['season_number' => 0, 'episode_count' => 3], ['season_number' => 1, 'episode_count' => 9], ['season_number' => 2, 'episode_count' => 10]],
        ]),
        'image.tmdb.org/*' => Http::response($poster, 200, ['Content-Type' => 'image/jpeg']),
    ]);
}

it('searches TMDB and imports a series with its seasons, poster and accent', function () {
    fakeTmdb();

    $component = Livewire::test('pages::admin.watched.create')
        ->set('type', 'series')
        ->set('query', 'Severance')
        ->call('search')
        ->assertSee('Severance');

    $component->call('import', 95396)->assertRedirect();

    $series = Watchable::sole();
    expect($series->title)->toBe('Severance')
        ->and($series->creator)->toBe('Dan Erickson')
        ->and($series->genres)->toBe(['Dram', 'Gizem'])
        ->and($series->cast)->toBe([['name' => 'Adam Scott', 'role' => 'Mark Scout']])
        ->and($series->seasons->pluck('episode_count', 'number')->all())->toBe([1 => 9, 2 => 10])
        ->and($series->poster_path)->not->toBeNull()
        ->and($series->accent)->toStartWith('#')
        ->and($series->published_at)->toBeNull();

    Http::assertSent(fn ($request) => $request->hasHeader('Authorization', 'Bearer test-token'));
});

it('opens the existing entry instead of importing it twice', function () {
    fakeTmdb();
    $existing = Watchable::factory()->series()->create(['tmdb_id' => 95396]);

    Livewire::test('pages::admin.watched.create')
        ->set('type', 'series')
        ->call('import', 95396)
        ->assertRedirect(route('admin.watched.edit', $existing));

    expect(Watchable::count())->toBe(1);
});

it('refreshes the facts from TMDB without touching Kadir\'s own data', function () {
    fakeTmdb();
    $series = Watchable::factory()->series(SeriesStatus::Watching)->create(['tmdb_id' => 95396, 'title' => 'Eski ad', 'rating' => 8.5, 'review' => 'Benim yorumum.']);
    $series->seasons()->create(['number' => 1, 'episode_count' => 5, 'note' => 'harika']);

    Livewire::test('pages::admin.watched.edit', ['watchable' => $series])->call('refreshFromTmdb');

    $series->refresh();
    expect($series->title)->toBe('Severance')
        ->and($series->rating)->toBe(8.5)
        ->and($series->review)->toBe('Benim yorumum.')
        ->and($series->seasons()->where('number', 1)->value('note'))->toBe('harika')
        ->and($series->seasons()->pluck('episode_count', 'number')->all())->toBe([1 => 9, 2 => 10]);
});

it('says so when TMDB fails during a search', function () {
    Http::fake(['api.themoviedb.org/*' => Http::response([], 500)]);

    Livewire::test('pages::admin.watched.create')
        ->set('query', 'Dune')
        ->call('search')
        ->assertHasErrors('query');
});

it('creates an entry by hand', function () {
    Livewire::test('pages::admin.watched.create')
        ->set('type', 'film')
        ->set('manualTitle', 'Ev Videosu')
        ->call('createManually')
        ->assertRedirect();

    expect(Watchable::sole()->only(['title', 'accent']))->toBe(['title' => 'Ev Videosu', 'accent' => '#c42452']);
});

it('saves the rating, favorite, review and series progress', function () {
    $series = Watchable::factory()->series()->create();

    Livewire::test('pages::admin.watched.edit', ['watchable' => $series])
        ->set('form.rating', '7.5')
        ->set('form.is_favorite', true)
        ->set('form.series_status', SeriesStatus::Finished->value)
        ->set('form.current_season', 2)
        ->set('form.current_episode', 10)
        ->set('form.review', ":::spoiler\nSon.\n:::")
        ->call('publishReviewNow')
        ->call('save')
        ->assertHasNoErrors();

    $series->refresh();
    expect($series->rating)->toBe(7.5)
        ->and($series->is_favorite)->toBeTrue()
        ->and($series->series_status)->toBe(SeriesStatus::Finished)
        ->and($series->hasPublishedReview())->toBeTrue()
        ->and($series->review_html)->toContain('data-spoiler');
});

it('rejects a rating that is not a half step out of ten', function (string $rating) {
    Livewire::test('pages::admin.watched.edit', ['watchable' => Watchable::factory()->create()])
        ->set('form.rating', $rating)
        ->call('save')
        ->assertHasErrors('form.rating');
})->with(['7.3', '11.0', '-1.0']);

it('keeps the series fields away from films', function () {
    $film = Watchable::factory()->create();

    Livewire::test('pages::admin.watched.edit', ['watchable' => $film])
        ->set('form.series_status', SeriesStatus::Watching->value)
        ->call('save')
        ->assertHasErrors('form.series_status');
});

it('reads the cast from "Name — Role" lines', function () {
    $film = Watchable::factory()->create();

    Livewire::test('pages::admin.watched.edit', ['watchable' => $film])
        ->set('form.castText', "Sandra Hüller — Sandra\nMilo Machado-Graner - Daniel\nYalnız İsim")
        ->call('save');

    expect($film->fresh()->cast)->toBe([
        ['name' => 'Sandra Hüller', 'role' => 'Sandra'],
        ['name' => 'Milo Machado-Graner', 'role' => 'Daniel'],
        ['name' => 'Yalnız İsim', 'role' => ''],
    ]);
});

it('adds viewings to the diary and rejects future dates', function () {
    $film = Watchable::factory()->create();
    $component = Livewire::test('pages::admin.watched.edit', ['watchable' => $film]);

    $component->set('viewingDate', '2026-09-01')->set('viewingPlace', 'Sinemada')->call('addViewing')->assertHasNoErrors();
    $component->set('viewingDate', now()->addDay()->toDateString())->call('addViewing')->assertHasErrors('viewingDate');

    expect($film->viewings()->pluck('place')->all())->toBe(['Sinemada']);
});

it('edits the seasons of a series', function () {
    $series = Watchable::factory()->series()->create();
    $season = $series->seasons()->create(['number' => 1, 'episode_count' => 8]);

    Livewire::test('pages::admin.watched.edit', ['watchable' => $series])
        ->set("seasonRows.{$season->id}.rating", '9.0')
        ->set("seasonRows.{$season->id}.note", 'en iyisi')
        ->call('saveSeasons')
        ->call('addSeason')
        ->assertHasNoErrors();

    expect($season->fresh()->only(['rating', 'note']))->toBe(['rating' => 9.0, 'note' => 'en iyisi'])
        ->and($series->seasons()->pluck('number')->all())->toBe([1, 2]);
});

it('stores an uploaded poster and takes its accent', function () {
    $film = Watchable::factory()->create();

    Livewire::test('pages::admin.watched.edit', ['watchable' => $film])
        ->set('poster', UploadedFile::fake()->image('afis.png', 300, 450))
        ->assertHasNoErrors();

    expect($film->fresh()->poster_path)->toStartWith('posters/');
});

it('deletes an entry with its poster files', function () {
    $film = Watchable::factory()->create();
    $component = Livewire::test('pages::admin.watched.edit', ['watchable' => $film])
        ->set('poster', UploadedFile::fake()->image('afis.png', 300, 450));
    $poster = $film->fresh()->poster_path;

    $component->call('delete')->assertRedirect(route('admin.watched.index'));

    $this->assertModelMissing($film);
    expect(Storage::disk('public')->allFiles('posters'))->toBe([])
        ->and($poster)->not->toBeNull();
});
