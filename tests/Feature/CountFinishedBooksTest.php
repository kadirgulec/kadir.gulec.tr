<?php

use App\Actions\Goals\CopyYearlyGoals;
use App\Enums\ReadingStatus;
use App\Models\Book;
use App\Models\Goal;
use App\Models\Reading;

beforeEach(fn () => $this->travelTo('2026-10-13'));

/**
 * The book entries of a goal: [date, note] pairs, oldest first.
 *
 * @return list<array{0: string, 1: ?string}>
 */
function bookEntries(Goal $goal): array
{
    return $goal->progressEntries()->whereNotNull('reading_id')->reorder('date')->get()
        ->map(fn ($entry): array => [$entry->date->toDateString(), $entry->note])->all();
}

it('adds one to the counting goal of the year a book was finished in', function () {
    $goal = Goal::factory()->countingBooks()->create(['target' => 20]);
    $book = Book::factory()->create(['title' => 'Körlük']);

    $reading = Reading::factory()->reading()->for($book)->create(['started_on' => '2026-09-01']);
    expect($goal->current())->toBe(0);

    $reading->update(['status' => ReadingStatus::Finished, 'finished_on' => '2026-10-10']);

    expect(bookEntries($goal))->toBe([['2026-10-10', 'Körlük']])
        ->and($goal->current())->toBe(1);
});

it('counts rereads but not abandoned books, and leaves goals without the switch alone', function () {
    $goal = Goal::factory()->countingBooks()->create();
    $manual = Goal::factory()->yearly()->create();
    $book = Book::factory()->create();

    Reading::factory()->for($book)->create(['started_on' => '2026-01-01', 'finished_on' => '2026-02-01']);
    Reading::factory()->for($book)->create(['started_on' => '2026-05-01', 'finished_on' => '2026-06-01']);
    Reading::factory()->abandoned()->create(['started_on' => '2026-03-01', 'finished_on' => '2026-03-10']);

    expect($goal->current())->toBe(2)
        ->and($manual->current())->toBe(0);
});

it('takes the entry away when the reading is no longer finished, or is deleted', function () {
    $goal = Goal::factory()->countingBooks()->create();
    $undone = Reading::factory()->create(['started_on' => '2026-01-01', 'finished_on' => '2026-02-01']);
    $deleted = Reading::factory()->create(['started_on' => '2026-03-01', 'finished_on' => '2026-04-01']);

    $undone->update(['status' => ReadingStatus::Abandoned]);
    $deleted->book->delete();

    expect($goal->current())->toBe(0);
});

it('moves the entry to the goal of the year the finish date moved to', function () {
    $lastYear = Goal::factory()->countingBooks(2025)->create();
    $thisYear = Goal::factory()->countingBooks(2026)->create();
    $reading = Reading::factory()->create(['started_on' => '2025-12-01', 'finished_on' => '2026-01-03']);

    expect([$lastYear->current(), $thisYear->current()])->toBe([0, 1]);

    $reading->update(['finished_on' => '2025-12-30']);

    expect([$lastYear->current(), $thisYear->current()])->toBe([1, 0]);
});

it('counts the books finished before the switch was turned on, and lets them go when it is off again', function () {
    Reading::factory()->for(Book::factory()->create(['title' => 'Birinci']))->create(['started_on' => '2026-01-01', 'finished_on' => '2026-01-20']);
    Reading::factory()->for(Book::factory()->create(['title' => 'İkinci']))->create(['started_on' => '2026-02-01', 'finished_on' => '2026-03-05']);
    Reading::factory()->create(['started_on' => '2025-11-01', 'finished_on' => '2025-12-20']);
    $goal = Goal::factory()->yearly()->create();
    $goal->progressEntries()->create(['date' => '2026-01-01', 'amount' => 1, 'note' => 'elle']);

    $goal->update(['counts_books' => true]);

    expect(bookEntries($goal))->toBe([['2026-01-20', 'Birinci'], ['2026-03-05', 'İkinci']])
        ->and($goal->current())->toBe(3);

    $goal->update(['counts_books' => false]);

    expect($goal->progressEntries()->pluck('note')->all())->toBe(['elle']);
});

it('copies the switch into the new year, which counts the books already finished in it', function () {
    $this->travelTo('2027-01-15');
    $lastYear = Goal::factory()->countingBooks(2026)->create(['target' => 20]);
    Reading::factory()->create(['started_on' => '2026-12-20', 'finished_on' => '2027-01-10']);

    [$copy] = app(CopyYearlyGoals::class)->handle([$lastYear->id], 2027);

    expect($copy->counts_books)->toBeTrue()
        ->and($copy->current())->toBe(1);
});
