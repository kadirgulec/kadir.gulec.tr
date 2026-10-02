<?php

use App\Enums\ChainDayState;
use App\Enums\GoalKind;
use App\Enums\GoalMeasure;
use App\Enums\Permission;
use App\Models\Goal;
use App\Models\Role;
use App\Models\User;
use Carbon\CarbonImmutable;
use Livewire\Livewire;

beforeEach(function () {
    $this->actingAs(User::factory()->admin()->create());
});

describe('editor', function () {
    it('creates a chain, a yearly goal and a long-term goal', function (string $segment, array $fields, GoalKind $kind) {
        $component = Livewire::test('pages::admin.goals.edit', ['kind' => $segment])->set('form.title', 'Yeni hedef');

        foreach ($fields as $field => $value) {
            $component->set("form.$field", $value);
        }

        $component->call('save')->assertHasNoErrors()->assertRedirect();

        expect(Goal::sole()->kind)->toBe($kind);
    })->with([
        'chain' => ['zincir', ['started_on' => '2026-01-01'], GoalKind::Chain],
        'yearly' => ['yillik', ['measure' => 'numeric', 'target' => 12, 'unit' => 'kitap'], GoalKind::Yearly],
        'long-term' => ['uzun-vade', ['why' => 'Çünkü ==önemli==.'], GoalKind::LongTerm],
    ]);

    it('starts new goals hidden, so nothing shows up before Kadir opens it', function () {
        Livewire::test('pages::admin.goals.edit', ['kind' => 'zincir'])->set('form.title', 'Sessiz başlangıç')->call('save');

        expect(Goal::sole()->visibility->value)->toBe('hidden');
    });

    it('only accepts a long-term goal as a parent', function () {
        $chain = Goal::factory()->chain()->create();

        Livewire::test('pages::admin.goals.edit', ['kind' => 'yillik'])
            ->set('form.title', 'Alt hedef')
            ->set('form.target', 5)
            ->set('form.parent_id', (string) $chain->id)
            ->call('save')
            ->assertHasErrors(['form.parent_id' => 'exists']);
    });

    it('gives a long-term goal no parent', function () {
        $longTerm = Goal::factory()->longTerm()->create();
        $other = Goal::factory()->longTerm()->create();

        Livewire::test('pages::admin.goals.edit', ['goal' => $longTerm])
            ->set('form.parent_id', (string) $other->id)
            ->call('save')
            ->assertHasErrors(['form.parent_id' => 'prohibited']);
    });

    it('requires a target for a numeric goal', function () {
        Livewire::test('pages::admin.goals.edit', ['kind' => 'yillik'])
            ->set('form.title', 'Sayısal')
            ->set('form.measure', 'numeric')
            ->call('save')
            ->assertHasErrors(['form.target' => 'required']);
    });

    it('returns 404 for an unknown kind', function () {
        $this->get('/admin/hedefler/yeni/kitap')->assertNotFound();
    });
});

describe('chain days', function () {
    it('cycles a day in the grid from missed to done to excused and back', function () {
        $chain = Goal::factory()->chain('---')->create();
        $day = CarbonImmutable::yesterday()->toDateString();
        $component = Livewire::test('pages::admin.goals.edit', ['goal' => $chain]);

        $component->call('cycleDay', $day);
        expect($chain->chainDays()->sole()->state)->toBe(ChainDayState::Done);

        $component->call('cycleDay', $day);
        expect($chain->chainDays()->sole()->state)->toBe(ChainDayState::Excused);

        $component->call('cycleDay', $day);
        expect($chain->chainDays()->count())->toBe(0);
    });

    it('locks future days and days before the chain started', function (string $date) {
        $chain = Goal::factory()->chain('---')->create();

        Livewire::test('pages::admin.goals.edit', ['goal' => $chain])
            ->call('cycleDay', $date)
            ->assertHasErrors('date');

        expect($chain->chainDays()->count())->toBe(0);
    })->with([
        'tomorrow' => fn () => CarbonImmutable::tomorrow()->toDateString(),
        'before start' => fn () => CarbonImmutable::today()->subDays(10)->toDateString(),
    ]);
});

describe('dashboard', function () {
    it('marks today and yesterday with one tap and takes it back on a second tap', function () {
        $chain = Goal::factory()->chain('--')->create(['started_on' => CarbonImmutable::today()->subDays(3)]);
        $component = Livewire::test('pages::admin.dashboard');

        $component->call('mark', $chain->id, 'today', 'done');
        $component->call('mark', $chain->id, 'yesterday', 'excused');
        expect($chain->chainDays()->pluck('state', 'date')->mapWithKeys(fn ($state, $date) => [substr($date, 0, 10) => $state->value])->all())
            ->toBe([CarbonImmutable::yesterday()->toDateString() => 'excused', CarbonImmutable::today()->toDateString() => 'done']);

        $component->call('mark', $chain->id, 'today', 'done');
        expect($chain->chainDays()->whereDate('date', today())->exists())->toBeFalse();
    });

    it('adds one to a numeric goal with an optional note', function () {
        $goal = Goal::factory()->yearly()->create(['title' => '12 kitap oku']);

        Livewire::test('pages::admin.dashboard')
            ->set("progressNotes.{$goal->id}", 'Tutunamayanlar')
            ->call('addOne', $goal->id)
            ->assertHasNoErrors();

        expect($goal->fresh()->current())->toBe(1)
            ->and($goal->progressEntries()->value('note'))->toBe('Tutunamayanlar');
    });

    it('does not show the goal tools to an admin user without the goals permission', function () {
        $role = Role::create(['name' => 'editor', 'label' => 'Editör', 'guard_name' => 'web']);
        $role->givePermissionTo(Permission::AccessAdmin->value);
        $editor = User::factory()->withTwoFactor()->create();
        $editor->assignRole($role);
        $chain = Goal::factory()->chain('-')->create();

        $this->actingAs($editor);

        Livewire::test('pages::admin.dashboard')
            ->assertDontSee($chain->title)
            ->call('mark', $chain->id, 'today', 'done')
            ->assertForbidden();
    });
});

describe('yearly goal details', function () {
    it('keeps milestones, the achievement stamp and progress entries', function () {
        $milestones = Goal::factory()->yearly(GoalMeasure::Milestones)->create();
        $binary = Goal::factory()->yearly(GoalMeasure::Binary)->create();
        $numeric = Goal::factory()->yearly()->create(['target' => 2]);

        $component = Livewire::test('pages::admin.goals.edit', ['goal' => $milestones])->set('milestoneTitle', 'Beta')->call('addMilestone');
        $milestone = $milestones->milestones()->sole();
        $component->call('toggleMilestone', $milestone->id);
        expect($milestones->fresh(['milestones'])->isAchieved())->toBeTrue();

        Livewire::test('pages::admin.goals.edit', ['goal' => $binary])->call('toggleAchieved');
        expect($binary->fresh()->achieved_at)->not->toBeNull();

        Livewire::test('pages::admin.goals.edit', ['goal' => $numeric])
            ->set('progressAmount', 2)->call('addProgress')->assertHasNoErrors();
        expect($numeric->fresh()->isAchieved())->toBeTrue();
    });

    it('copies last year\'s goals into this year without their progress', function () {
        $old = Goal::factory()->yearly(GoalMeasure::Milestones, now()->year - 1)->create(['title' => 'Yarı maraton koş']);
        $old->milestones()->create(['title' => 'İlk 10 km', 'done_at' => now()]);

        Livewire::test('pages::admin.goals.index')
            ->set('copyIds', [$old->id])
            ->call('copyFromPreviousYear')
            ->assertHasNoErrors();

        $copy = Goal::query()->where('year', now()->year)->sole();
        expect($copy->title)->toBe('Yarı maraton koş')
            ->and($copy->milestones()->sole()->only(['title', 'done_at']))->toBe(['title' => 'İlk 10 km', 'done_at' => null]);
    });
});

it('keeps the children of a deleted long-term goal', function () {
    $longTerm = Goal::factory()->longTerm()->create();
    $chain = Goal::factory()->chain()->create(['parent_id' => $longTerm->id]);

    Livewire::test('pages::admin.goals.edit', ['goal' => $longTerm])->call('delete')->assertRedirect(route('admin.goals.index'));

    $this->assertModelMissing($longTerm);
    expect($chain->fresh()->parent_id)->toBeNull();
});

it('moves a goal to a new position among its kind', function () {
    [$a, $b] = Goal::factory()->chain()->count(2)->sequence(['sort_order' => 0], ['sort_order' => 1])->create();

    Livewire::test('pages::admin.goals.index')->call('sort', $b->id, 0);

    expect(Goal::query()->orderBy('sort_order')->pluck('id')->all())->toBe([$b->id, $a->id]);
});

it('keeps members out of the goal screens', function () {
    $this->actingAs(User::factory()->member()->create())->get(route('admin.goals.index'))->assertNotFound();
});
