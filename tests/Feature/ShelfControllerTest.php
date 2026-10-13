<?php

use App\Enums\SeriesStatus;
use App\Models\Watchable;

/*
 * Prototype (RAFIM.md, step 2): books are fixed demo data, films and series come from the database.
 */

beforeEach(fn () => $this->travelTo('2026-10-13'));

it('puts viewings and readings into one diary, grouped by month', function () {
    Watchable::factory()->hasViewings(1, ['watched_on' => '2026-09-25'])->create(['title' => 'Perfect Days']);
    Watchable::factory()->hasViewings(1, ['watched_on' => '2026-08-20'])->create(['title' => 'Bir Düşüşün Anatomisi']);

    $this->get(route('shelf.index'))
        ->assertSeeTextInOrder(['Günlük', 'Eylül 2026', 'Perfect Days', 'Kürk Mantolu Madonna', 'Ağustos 2026', 'Bir Düşüşün Anatomisi', 'Körlük']);
});

it('shows what is being read and watched right now', function () {
    Watchable::factory()->series(SeriesStatus::Watching)->hasViewings(1)->create(['title' => 'Shōgun']);

    $this->get(route('shelf.index'))
        ->assertSeeTextInOrder(['Şu an okuyorum', 'Saatleri Ayarlama Enstitüsü', 'Şu an izliyorum', 'Shōgun']);
});

it('offers the first three of each list and a link to the rest', function () {
    foreach (['Dune Part Two', 'Anora', 'Flow', 'Nosferatu'] as $title) {
        Watchable::factory()->draft()->create(['title' => $title])->addToWatchlist();
    }

    $this->get(route('shelf.index'))
        ->assertSeeTextInOrder(['Okuyacaklarım', 'Huzur', 'Dune', 'Kafamda Bir Tuhaflık', 'hepsi (5)', 'İzleyeceklerim', 'Dune Part Two', 'Anora', 'Flow', 'hepsi (4)'])
        ->assertDontSeeText('Thinking, Fast and Slow')
        ->assertDontSeeText('Nosferatu');
});

it('marks the shelf tab on its own page and on both lists', function (string $routeName, string $label) {
    $html = $this->get(route($routeName))->getContent();

    expect($html)->toMatch('#<nav aria-label="Rafım".*?aria-current="page"[^>]*>'.$label.'</a>#s');
})->with([
    'all' => ['shelf.index', 'Hepsi'],
    'watched' => ['watched.index', 'İzlediklerim'],
    'books' => ['books.index', 'Okuduklarım'],
]);
