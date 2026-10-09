<?php

use App\Enums\Permission;
use App\Enums\ReviewItemKind;
use App\Enums\ReviewItemOutcome;
use App\Models\Goal;
use App\Models\MonthlyReview;
use App\Models\ReviewItem;
use App\Models\Role;
use App\Models\User;
use Carbon\CarbonImmutable;
use Livewire\Livewire;

beforeEach(function () {
    $this->travelTo(CarbonImmutable::parse('2026-11-02 10:00'));
});

describe('access', function () {
    it('keeps the review screens to those who manage goals', function () {
        $role = Role::create(['name' => 'editor', 'label' => 'Editör', 'guard_name' => 'web']);
        $role->givePermissionTo(Permission::AccessAdmin->value);
        $editor = User::factory()->withTwoFactor()->create();
        $editor->assignRole($role);
        $review = MonthlyReview::factory()->forMonth('2026-10')->draft()->create();

        $this->actingAs($editor);

        $this->get(route('admin.reviews.index'))->assertForbidden();
        $this->get(route('admin.reviews.edit', $review))->assertForbidden();
        Livewire::test('pages::admin.dashboard')->assertDontSee('değerlendirmesi');
    });
});

describe('list', function () {
    beforeEach(function () {
        $this->actingAs(User::factory()->admin()->create());
    });

    it('makes a month by hand with its numbers and opens it', function () {
        $chain = Goal::factory()->chain('')->create(['started_on' => '2026-09-01']);
        $chain->chainDays()->create(['date' => '2026-09-15', 'state' => 'done']);

        Livewire::test('pages::admin.reviews.index')
            ->assertSet('month', '2026-10')
            ->set('month', '2026-09')
            ->call('create')
            ->assertHasNoErrors()
            ->assertRedirect(route('admin.reviews.edit', MonthlyReview::sole()));

        $review = MonthlyReview::sole();
        expect($review->month->toDateString())->toBe('2026-09-01')
            ->and($review->published_at)->toBeNull()
            ->and($review->stats['chains'][0]['doneDays'])->toBe(1);
    });

    it('does not make a month twice or one in another shape', function (string $month, string $message) {
        MonthlyReview::factory()->forMonth('2026-10')->create();

        Livewire::test('pages::admin.reviews.index')
            ->set('month', $month)
            ->call('create')
            ->assertHasErrors(['month'])
            ->assertSee($message);

        expect(MonthlyReview::query()->count())->toBe(1);
    })->with([
        'existing' => ['2026-10', 'Ekim 2026 zaten var.'],
        'not a month' => ['ekim', 'ay alanı Y-m biçiminde olmalı.'],
    ]);
});

describe('edit', function () {
    beforeEach(function () {
        $this->actingAs(User::factory()->admin()->create());
    });

    it('saves the summary, the score and the publication date', function () {
        $review = MonthlyReview::factory()->forMonth('2026-10')->draft()->create(['summary' => null, 'score' => null]);

        Livewire::test('pages::admin.reviews.edit', ['review' => $review])
            ->set('summary', '  Spor oturdu.  ')
            ->set('score', '8')
            ->call('publishNow')
            ->call('save')
            ->assertHasNoErrors();

        $review->refresh();
        expect($review->summary)->toBe('Spor oturdu.')
            ->and($review->score)->toBe(8)
            ->and($review->isPublished())->toBeTrue();
    });

    it('rejects a score outside 1 to 10', function (string $score) {
        $review = MonthlyReview::factory()->forMonth('2026-10')->create(['score' => 5]);

        Livewire::test('pages::admin.reviews.edit', ['review' => $review])
            ->set('score', $score)
            ->call('save')
            ->assertHasErrors(['score']);

        expect($review->refresh()->score)->toBe(5);
    })->with(['zero' => '0', 'eleven' => '11', 'a word' => 'sekiz']);

    it('adds lines to each list in order, then edits, moves and deletes them', function () {
        $review = MonthlyReview::factory()->forMonth('2026-10')->create();

        $component = Livewire::test('pages::admin.reviews.edit', ['review' => $review])
            ->set('newItems.good', 'Her hafta spor')
            ->call('addItem', 'good')
            ->set('newItems.good', 'Kitap bitti')
            ->call('addItem', 'good')
            ->set('newItems.try', 'Pazar sabahı yazı')
            ->call('addItem', 'try')
            ->assertHasNoErrors()
            ->assertSet('newItems.good', '');

        $good = fn (): array => $review->items()->where('kind', ReviewItemKind::Good)->pluck('body')->all();
        expect($good())->toBe(['Her hafta spor', 'Kitap bitti'])
            ->and($review->items()->where('kind', ReviewItemKind::Try)->sole()->body)->toBe('Pazar sabahı yazı');

        $second = $review->items()->where('body', 'Kitap bitti')->sole();
        $component->call('sortItem', $second->id, 0);
        expect($good())->toBe(['Kitap bitti', 'Her hafta spor']);

        $component->call('startEditing', $second->id)->set('editingBody', 'Kitabı bitirdim')->call('updateItem');
        expect($good())->toBe(['Kitabı bitirdim', 'Her hafta spor']);

        $component->call('deleteItem', $second->id);
        expect($good())->toBe(['Her hafta spor']);
    });

    it('asks for the text of a new line', function () {
        $review = MonthlyReview::factory()->forMonth('2026-10')->create();

        Livewire::test('pages::admin.reviews.edit', ['review' => $review])
            ->call('addItem', 'hard')
            ->assertHasErrors(['newItems.hard']);

        expect($review->items()->count())->toBe(0);
    });

    it('does not touch the lines of another review', function () {
        $review = MonthlyReview::factory()->forMonth('2026-10')->create();
        $other = ReviewItem::factory()->for(MonthlyReview::factory()->forMonth('2026-09'), 'review')->create();

        Livewire::test('pages::admin.reviews.edit', ['review' => $review])
            ->call('deleteItem', $other->id)
            ->assertNotFound();

        expect($other->fresh())->not->toBeNull();
    });

    it('takes a suggestion into the list Kadir picks and drops it from the suggestions', function () {
        $review = MonthlyReview::factory()->forMonth('2026-10')->create();

        $component = Livewire::test('pages::admin.reviews.edit', ['review' => $review])
            ->assertSee('Bu ay hiç yazı yayınlanmadı')
            ->call('takeSuggestion', 0, 'hard');

        expect($review->items()->sole()->only(['kind', 'body']))->toBe(['kind' => ReviewItemKind::Hard, 'body' => 'Bu ay hiç yazı yayınlanmadı']);
        $component->assertDontSee('Zorlandıklarıma');
    });

    it('records how last month\'s "try" items went', function () {
        $september = MonthlyReview::factory()->forMonth('2026-09')->create();
        $try = ReviewItem::factory()->for($september, 'review')->toTry()->create(['body' => 'Sabah Almancası']);
        $review = MonthlyReview::factory()->forMonth('2026-10')->create();

        $component = Livewire::test('pages::admin.reviews.edit', ['review' => $review])
            ->assertSee("Ekim'de denediklerim")
            ->call('setOutcome', $try->id, 'not-done');

        expect($try->refresh()->outcome)->toBe(ReviewItemOutcome::NotDone);

        $component->call('setOutcome', $try->id, null);
        expect($try->refresh()->outcome)->toBeNull();
    });

    it('only records outcomes for last month\'s "try" items', function () {
        $review = MonthlyReview::factory()->forMonth('2026-10')->create();
        $ownTry = ReviewItem::factory()->for($review, 'review')->toTry()->create();

        Livewire::test('pages::admin.reviews.edit', ['review' => $review])
            ->call('setOutcome', $ownTry->id, 'done')
            ->assertNotFound();

        expect($ownTry->refresh()->outcome)->toBeNull();
    });

    it('hides a tile and shows it again', function () {
        $review = MonthlyReview::factory()->forMonth('2026-10')->create();

        $component = Livewire::test('pages::admin.reviews.edit', ['review' => $review])->call('toggleTile', 'views');
        expect($review->refresh()->hidden_stats)->toBe(['views']);

        $component->call('toggleTile', 'views');
        expect($review->refresh()->hidden_stats)->toBeNull();
    });

    it('does not hide a tile the review does not have', function () {
        $review = MonthlyReview::factory()->forMonth('2026-10')->create();

        Livewire::test('pages::admin.reviews.edit', ['review' => $review])
            ->call('toggleTile', 'chain:999')
            ->assertNotFound();
    });

    it('works the numbers out again', function () {
        $review = MonthlyReview::factory()->forMonth('2026-10')->create();
        $chain = Goal::factory()->chain('')->create(['started_on' => '2026-10-01']);
        $chain->chainDays()->create(['date' => '2026-10-20', 'state' => 'done']);

        Livewire::test('pages::admin.reviews.edit', ['review' => $review])->call('refreshStats');

        expect($review->refresh()->stats['chains'][0])->toMatchArray(['goal_id' => $chain->id, 'doneDays' => 1]);
    });

    it('deletes the review with its lines', function () {
        $review = MonthlyReview::factory()->forMonth('2026-10')->create();
        ReviewItem::factory()->for($review, 'review')->create();

        Livewire::test('pages::admin.reviews.edit', ['review' => $review])
            ->call('delete')
            ->assertRedirect(route('admin.reviews.index'));

        expect(MonthlyReview::query()->count())->toBe(0)
            ->and(ReviewItem::query()->count())->toBe(0);
    });
});

it('reminds Kadir of a review waiting as a draft on the dashboard', function () {
    $this->actingAs(User::factory()->admin()->create());
    $review = MonthlyReview::factory()->forMonth('2026-10')->draft()->create();

    Livewire::test('pages::admin.dashboard')
        ->assertSee('Ekim 2026 değerlendirmesi')
        ->assertSee(route('admin.reviews.edit', $review));

    $review->update(['published_at' => now()]);

    Livewire::test('pages::admin.dashboard')->assertDontSee('Ekim 2026 değerlendirmesi');
});
