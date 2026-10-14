<?php

use App\Enums\ReadingStatus;
use App\Models\Book;
use App\Models\Quote;
use App\Models\Reading;
use App\Models\Watchable;
use Illuminate\Support\Facades\Storage;

it('tells two books with the same title apart by their author, then by a number', function () {
    $first = Book::factory()->create(['title' => 'Yalnızız', 'author' => 'Peyami Safa']);
    $second = Book::factory()->create(['title' => 'Yalnızız', 'author' => 'Başka Yazar']);
    $third = Book::factory()->create(['title' => 'Yalnızız', 'author' => 'Başka Yazar']);

    expect([$first->slug, $second->slug, $third->slug])->toBe(['yalniziz', 'yalniziz-baska-yazar', 'yalniziz-baska-yazar-2']);
});

it('keeps the reading list in order and takes a book off it when a reading starts', function () {
    $first = Book::factory()->onReadingList('Ali önerdi')->create();
    $second = Book::factory()->onReadingList()->create();
    $third = Book::factory()->onReadingList()->create();

    $third->moveInReadingList(0);
    Reading::factory()->reading()->for($second)->create();

    expect(Book::query()->onReadingList()->pluck('id')->all())->toBe([$third->id, $first->id])
        ->and($first->fresh()->reading_list_note)->toBe('Ali önerdi')
        ->and($second->fresh()->isOnReadingList())->toBeFalse()
        ->and($second->fresh()->reading_list_note)->toBeNull();
});

it('puts a book on the currently reading shelf by its latest reading only', function () {
    $reading = Book::factory()->create();
    Reading::factory()->for($reading)->create(['started_on' => '2019-02-10', 'finished_on' => '2019-04-28']);
    Reading::factory()->paused()->for($reading)->create(['started_on' => '2026-09-01']);

    $reread = Book::factory()->create();
    Reading::factory()->reading()->for($reread)->create(['started_on' => '2019-02-10']);
    Reading::factory()->for($reread)->create(['started_on' => '2026-08-01', 'finished_on' => '2026-08-20']);

    expect(Book::query()->inProgress()->pluck('id')->all())->toBe([$reading->id]);
});

it('marks a finished reading as read to the end, and keeps where an abandoned one stopped', function () {
    $finished = Reading::factory()->create(['progress' => 92]);
    $abandoned = Reading::factory()->abandoned(38)->create();

    expect($finished->progress)->toBe(100)
        ->and($abandoned->progress)->toBe(38)
        ->and($abandoned->status)->toBe(ReadingStatus::Abandoned);
});

it('collects quotes for books and films in their own order, and drops them with their source', function () {
    $book = Book::factory()->create();
    $film = Watchable::factory()->create();

    $first = $book->quotes()->create(['body' => 'Birinci.']);
    $second = $book->quotes()->create(['body' => 'İkinci.']);
    $film->quotes()->create(['body' => 'Filmden.', 'said_by' => 'Samet', 'location' => '01:42']);
    $second->moveTo(0);

    expect($book->quotes()->pluck('body')->all())->toBe(['İkinci.', 'Birinci.'])
        ->and($film->quotes()->sole()->quotable)->toBeInstanceOf(Watchable::class)
        ->and($first->fresh()->quotable_type)->toBe('book');

    $book->delete();

    expect(Quote::query()->pluck('body')->all())->toBe(['Filmden.']);
});

it('renders the review and deletes the stored cover with the book', function () {
    Storage::fake('public');
    Storage::disk('public')->put('covers/kurk-480.webp', 'image');
    $book = Book::factory()->create(['review' => "Güzel.\n\n:::alıntı Raif Efendi\nBir cümle.\n:::"]);
    $book->forceFill(['cover_path' => 'covers/kurk'])->save();

    expect((string) $book->review_html)->toContain('Raif Efendi');

    $book->delete();

    expect(Storage::disk('public')->exists('covers/kurk-480.webp'))->toBeFalse();
});
