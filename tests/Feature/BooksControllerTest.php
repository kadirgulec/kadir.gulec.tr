<?php

use App\Models\Book;
use App\Models\Goal;
use App\Models\Reading;
use App\Models\User;

beforeEach(fn () => $this->travelTo('2026-10-13'));

/**
 * A published book with one finished reading between two days.
 *
 * @param  array<string, mixed>  $attributes
 */
function finishedBook(string $title, string $startedOn, string $finishedOn, array $attributes = []): Book
{
    $book = Book::factory()->create(['title' => $title, ...$attributes]);
    Reading::factory()->for($book)->create(['started_on' => $startedOn, 'finished_on' => $finishedOn]);

    return $book;
}

describe('index', function () {
    it('groups finished and abandoned readings of published books by year, newest first', function () {
        finishedBook('Kürk Mantolu Madonna', '2026-09-03', '2026-09-21');
        finishedBook('Tehlikeli Oyunlar', '2025-10-15', '2025-11-30');
        Reading::factory()->abandoned(38)->for(Book::factory()->create(['title' => 'Simyacı']))->create(['started_on' => '2026-03-20', 'finished_on' => '2026-04-02']);
        finishedBook('Taslak Kitap', '2026-08-01', '2026-08-10', ['published_at' => null]);

        $this->get(route('books.index'))
            ->assertViewHas('diaryByYear', fn (array $diary): bool => array_keys($diary) === [2026, 2025])
            ->assertSeeTextInOrder(['Günlük', '2026', 'Kürk Mantolu Madonna', 'Simyacı', '2025', 'Tehlikeli Oyunlar'])
            ->assertSeeText('yarım bıraktım · %38')
            ->assertDontSeeText('Taslak Kitap');
    });

    it('keeps books being read on the shelf and out of the diary', function () {
        Reading::factory()->reading(43)->for(Book::factory()->create(['title' => 'Saatleri Ayarlama Enstitüsü']))->create(['started_on' => '2026-09-28']);
        Reading::factory()->paused(71)->for(Book::factory()->create(['title' => 'Clean Code']))->create(['started_on' => '2026-06-02']);

        $this->get(route('books.index'))
            ->assertViewHas('currentlyReading', fn (array $shelf): bool => array_column($shelf, 'title') === ['Saatleri Ayarlama Enstitüsü', 'Clean Code'])
            ->assertViewHas('diaryByYear', []);
    });

    it('counts only finished books of this year and links the reading goal', function () {
        finishedBook('Bir', '2026-01-01', '2026-01-20');
        finishedBook('İki', '2026-02-01', '2026-02-20');
        finishedBook('Geçen Yıl', '2025-11-01', '2025-12-20');
        Reading::factory()->abandoned()->create(['started_on' => '2026-03-01', 'finished_on' => '2026-03-10']);
        $goal = Goal::factory()->yearly()->create(['target' => 20]);

        $this->get(route('books.index'))
            ->assertSee('2026: 2 kitap')
            ->assertSee('hedef 2 / 20')
            ->assertSee(route('goals.show', $goal->slug));
    });

    it('shows no goal when there is no reading goal', function () {
        $this->get(route('books.index'))->assertSee('2026: 0 kitap')->assertDontSee('hedef ');
    });

    it('shows the reading list in order, with its notes and without links', function () {
        Book::factory()->draft()->onReadingList('Ali önerdi')->create(['title' => 'Dune']);
        Book::factory()->draft()->onReadingList()->create(['title' => 'Huzur']);

        $this->get(route('books.index'))
            ->assertSeeTextInOrder(['Sırada', 'Dune', 'Ali önerdi', 'Huzur'])
            ->assertDontSee('/okuduklarim/dune');
    });
});

describe('show', function () {
    it('shows the review, its quote block and the visible collected quotes', function () {
        $book = finishedBook('Kürk Mantolu Madonna', '2026-09-03', '2026-09-21');
        $book->update(['review' => ":::alıntı Raif Efendi\nBir cümle.\n:::", 'review_published_at' => now()->subDay()]);
        $book->quotes()->create(['body' => 'Görünen.', 'said_by' => 'Maria Puder', 'location' => '%88', 'published_at' => now()]);
        $book->quotes()->create(['body' => 'Gizli alıntı.']);

        $this->get(route('books.show', $book->slug))
            ->assertSeeText('Yorumum')
            ->assertSee('— Raif Efendi')
            ->assertSeeTextInOrder(['Altını çizdiklerim', 'Görünen.', '— Maria Puder · %88'])
            ->assertDontSeeText('Gizli alıntı.');
    });

    it('hangs the bookmark with the percentage on a book being read', function () {
        $book = Book::factory()->create();
        Reading::factory()->reading(43)->for($book)->create(['started_on' => '2026-09-28']);

        $this->get(route('books.show', $book->slug))
            ->assertSeeText('%43')
            ->assertSeeText('28 Eylül\'de başladım')
            ->assertSeeText('Bu kitap hakkında henüz bir şey yazmadım.');
    });

    it('lists every reading of a book read twice', function () {
        $book = finishedBook('Tutunamayanlar', '2019-02-10', '2019-04-28');
        Reading::factory()->for($book)->create(['started_on' => '2025-12-01', 'finished_on' => '2026-01-20']);

        $this->get(route('books.show', $book->slug))
            ->assertSeeText('(tekrar okudum)')
            ->assertSeeTextInOrder(['Okumalarım', '1 Aralık 2025 – 20 Ocak 2026', '10 Şubat – 28 Nisan 2019']);
    });

    it('hides a draft from visitors and previews it, unread, for Kadir', function () {
        $book = Book::factory()->draft()->onReadingList()->create(['title' => 'Dune']);

        $this->get(route('books.show', $book->slug))->assertNotFound();

        $this->actingAs(User::factory()->admin()->create())
            ->get(route('books.show', $book->slug))
            ->assertOk()
            ->assertSeeText('Henüz okumadım');
    });

    it('answers 404 for an unknown book', function () {
        $this->get(route('books.show', 'yok-boyle-bir-kitap'))->assertNotFound();
    });
});
