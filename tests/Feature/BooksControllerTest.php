<?php

use App\Models\Goal;

/*
 * Prototype (RAFIM.md, step 2): the books are fixed demo data in BookContent.
 * These tests pin the page behavior; step 3 switches them to factories.
 */

beforeEach(fn () => $this->travelTo('2026-10-13'));

describe('index', function () {
    it('groups finished and abandoned readings by year, newest first', function () {
        $this->get(route('books.index'))
            ->assertViewHas('diaryByYear', fn (array $diary): bool => array_keys($diary) === [2026, 2025, 2019])
            ->assertSeeTextInOrder(['Günlük', '2026', 'Kürk Mantolu Madonna', 'Simyacı', 'Tutunamayanlar', '2025', 'Tehlikeli Oyunlar'])
            ->assertSeeText('yarım bıraktım · %38');
    });

    it('keeps books being read on the shelf and out of the diary', function () {
        $this->get(route('books.index'))
            ->assertViewHas('currentlyReading', fn (array $shelf): bool => array_column($shelf, 'title') === ['Saatleri Ayarlama Enstitüsü', 'Clean Code'])
            ->assertViewHas('diaryByYear', fn (array $diary): bool => collect($diary)->flatten(1)->pluck('title')->doesntContain('Clean Code'));
    });

    it('counts only finished books of this year and links the reading goal', function () {
        $goal = Goal::factory()->yearly()->create(['target' => 20]);

        $this->get(route('books.index'))
            ->assertSee('2026: 5 kitap')
            ->assertSee('hedef 5 / 20')
            ->assertSee(route('goals.show', $goal->slug));
    });

    it('shows no goal when there is no reading goal', function () {
        $this->get(route('books.index'))->assertSee('2026: 5 kitap')->assertDontSee('hedef ');
    });
});

describe('show', function () {
    it('shows the review, its quote block and the collected quotes', function () {
        $this->get(route('books.show', 'kurk-mantolu-madonna'))
            ->assertSeeText('Yorumum')
            ->assertSee('— Raif Efendi')
            ->assertSeeText('Altını çizdiklerim')
            ->assertSeeText('— Maria Puder · %88');
    });

    it('hangs the bookmark with the percentage on a book being read', function () {
        $this->get(route('books.show', 'saatleri-ayarlama-enstitusu'))
            ->assertSeeText('%43')
            ->assertSeeText('28 Eylül\'de başladım')
            ->assertSeeText('Bu kitap hakkında henüz bir şey yazmadım.');
    });

    it('lists every reading of a book read twice', function () {
        $this->get(route('books.show', 'tutunamayanlar'))
            ->assertSeeText('(tekrar okudum)')
            ->assertSeeTextInOrder(['Okumalarım', '1 Aralık 2025 – 20 Ocak 2026', '10 Şubat – 28 Nisan 2019']);
    });

    it('answers 404 for an unknown book', function () {
        $this->get(route('books.show', 'yok-boyle-bir-kitap'))->assertNotFound();
    });
});
