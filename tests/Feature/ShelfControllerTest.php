<?php

use App\Enums\SeriesStatus;
use App\Models\Book;
use App\Models\Reading;
use App\Models\Watchable;

beforeEach(fn () => $this->travelTo('2026-10-13'));

it('puts viewings and readings into one diary, grouped by month', function () {
    Watchable::factory()->hasViewings(1, ['watched_on' => '2026-09-25'])->create(['title' => 'Perfect Days']);
    Watchable::factory()->hasViewings(1, ['watched_on' => '2026-08-20'])->create(['title' => 'Bir Düşüşün Anatomisi']);
    Reading::factory()->for(Book::factory()->create(['title' => 'Kürk Mantolu Madonna']))->create(['started_on' => '2026-09-03', 'finished_on' => '2026-09-21']);
    Reading::factory()->for(Book::factory()->create(['title' => 'Körlük']))->create(['started_on' => '2026-07-20', 'finished_on' => '2026-08-10']);

    $this->get(route('shelf.index'))
        ->assertSeeTextInOrder(['Günlük', 'Eylül 2026', 'Perfect Days', 'Kürk Mantolu Madonna', 'Ağustos 2026', 'Bir Düşüşün Anatomisi', 'Körlük'])
        ->assertSee('bu yıl 2 film · 0 dizi · 2 kitap');
});

it('shows what is being read and watched right now', function () {
    Watchable::factory()->series(SeriesStatus::Watching)->hasViewings(1)->create(['title' => 'Shōgun']);
    Reading::factory()->reading()->for(Book::factory()->create(['title' => 'Saatleri Ayarlama Enstitüsü']))->create();

    $this->get(route('shelf.index'))
        ->assertSeeTextInOrder(['Şu an okuyorum', 'Saatleri Ayarlama Enstitüsü', 'Şu an izliyorum', 'Shōgun']);
});

it('offers the first three of each list and a link to the rest', function () {
    foreach (['Huzur', 'Dune', 'Kafamda Bir Tuhaflık', 'Thinking, Fast and Slow'] as $title) {
        Book::factory()->draft()->onReadingList()->create(['title' => $title]);
    }

    foreach (['Dune Part Two', 'Anora', 'Flow', 'Nosferatu'] as $title) {
        Watchable::factory()->draft()->create(['title' => $title])->addToWatchlist();
    }

    $this->get(route('shelf.index'))
        ->assertSeeTextInOrder(['Okuyacaklarım', 'Huzur', 'Dune', 'Kafamda Bir Tuhaflık', 'hepsi (4)', 'İzleyeceklerim', 'Dune Part Two', 'Anora', 'Flow', 'hepsi (4)'])
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
