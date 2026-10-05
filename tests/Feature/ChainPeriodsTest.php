<?php

use App\Enums\ChainPeriod;
use App\Mail\ChainReminder;
use App\Models\Goal;
use App\Models\User;
use App\Support\ChainReminders;
use Carbon\CarbonImmutable;
use Illuminate\Support\Facades\Mail;
use Livewire\Livewire;

/**
 * A chain started on $startedOn with the given marks (date => done|excused).
 * 5 October 2026 is a Monday.
 *
 * @param  array<string, string>  $marks
 */
function periodChain(ChainPeriod $period, int $target, string $startedOn, array $marks = [], array $attributes = []): Goal
{
    $chain = Goal::factory()->chain('')->per($period, $target)->create(['title' => 'Spor', 'started_on' => $startedOn, ...$attributes]);

    foreach ($marks as $date => $state) {
        $chain->chainDays()->create(['date' => $date, 'state' => $state]);
    }

    return $chain->load('chainDays');
}

function linkStates(Goal $chain): array
{
    return array_column($chain->load('chainDays')->chainLinks(), 'state');
}

describe('links', function () {
    it('adds up the days of a week into one link', function () {
        $this->travelTo(CarbonImmutable::parse('2026-10-21 12:00'));

        $chain = periodChain(ChainPeriod::Week, 2, '2026-09-28', [
            '2026-09-29' => 'done', '2026-10-02' => 'done',       // held
            '2026-10-06' => 'done', '2026-10-08' => 'excused',    // held with an excuse
            '2026-10-14' => 'done',                               // one short
            '2026-10-20' => 'done',                               // this week, not over yet
        ]);

        expect(linkStates($chain))->toBe(['done', 'excused', 'missed']);

        $chain->chainDays()->create(['date' => '2026-10-21', 'state' => 'done']);

        expect(linkStates($chain))->toBe(['done', 'excused', 'missed', 'done']);
    });

    it('does not let an excuse alone save a week', function () {
        $this->travelTo(CarbonImmutable::parse('2026-10-14 12:00'));

        $chain = periodChain(ChainPeriod::Week, 2, '2026-10-05', ['2026-10-07' => 'excused']);

        expect(linkStates($chain))->toBe(['missed']);
    });

    it('leaves out a partly covered first week unless it held', function () {
        $this->travelTo(CarbonImmutable::parse('2026-10-14 12:00'));

        expect(linkStates(periodChain(ChainPeriod::Week, 2, '2026-10-01')))->toBe(['missed'])
            ->and(linkStates(periodChain(ChainPeriod::Week, 2, '2026-10-01', ['2026-10-02' => 'done', '2026-10-03' => 'done'])))->toBe(['done', 'missed']);
    });

    it('counts months the same way', function () {
        $this->travelTo(CarbonImmutable::parse('2026-10-10 12:00'));

        $chain = periodChain(ChainPeriod::Month, 4, '2026-08-01', [
            '2026-08-03' => 'done', '2026-08-10' => 'done', '2026-08-17' => 'done', '2026-08-24' => 'done',
            '2026-09-07' => 'done', '2026-09-14' => 'done', '2026-09-21' => 'done',
        ]);

        expect(linkStates($chain))->toBe(['done', 'missed']);
    });

    it('keeps a daily chain one link per day', function () {
        $chain = Goal::factory()->chain('xx-ex')->create();

        expect(array_column($chain->chainLinks(), 'state'))->toBe(['done', 'done', 'missed', 'excused', 'done']);
    });
});

describe('reminders', function () {
    it('asks for help when the days left are at most twice the times needed', function (string $today, array $marks, ?string $text) {
        $this->travelTo(CarbonImmutable::parse($today.' 08:00'));

        $reminder = ChainReminders::morning(periodChain(ChainPeriod::Week, 2, '2026-09-28', $marks));

        expect($reminder['text'] ?? null)->toBe($text);
    })->with([
        'Wednesday, nothing yet' => ['2026-10-07', [], null],
        'Thursday, nothing yet' => ['2026-10-08', [], 'bu hafta 0/2, 4 günde 2 kez daha'],
        'Friday, one done' => ['2026-10-09', ['2026-10-08' => 'done'], null],
        'Saturday, one done' => ['2026-10-10', ['2026-10-08' => 'done'], 'bu hafta 1/2, 2 günde 1 kez daha'],
        'Saturday, an excuse fills it' => ['2026-10-10', ['2026-10-08' => 'done', '2026-10-09' => 'excused'], null],
    ]);

    it('gives each reminder a key per week and times still needed', function () {
        $this->travelTo(CarbonImmutable::parse('2026-10-08 08:00'));

        expect(ChainReminders::morning(periodChain(ChainPeriod::Week, 2, '2026-09-28'))['key'])->toBe('due-2026-10-05-2');
    });

    it('opens a wider window in a month and checks the pace from the 15th', function (string $today, array $marks, ?string $text) {
        $this->travelTo(CarbonImmutable::parse($today.' 08:00'));

        $reminder = ChainReminders::morning(periodChain(ChainPeriod::Month, 4, '2026-09-01', $marks));

        expect($reminder['text'] ?? null)->toBe($text);
    })->with([
        '23rd, half done' => ['2026-10-23', ['2026-10-05' => 'done', '2026-10-09' => 'done'], null],
        '24th, nothing yet' => ['2026-10-24', [], 'bu ay 0/4, 8 günde 4 kez daha'],
        '28th, half done' => ['2026-10-28', ['2026-10-05' => 'done', '2026-10-09' => 'done'], 'bu ay 2/4, 4 günde 2 kez daha'],
        '14th, behind' => ['2026-10-14', ['2026-10-05' => 'done'], null],
        '15th, behind' => ['2026-10-15', ['2026-10-05' => 'done'], 'ayın yarısı geçti, bu ay 1/4'],
        '15th, on pace' => ['2026-10-15', ['2026-10-05' => 'done', '2026-10-09' => 'done'], null],
    ]);

    it('does not ask a chain started on Sunday for two times that week', function () {
        $this->travelTo(CarbonImmutable::parse('2026-10-11 08:00'));

        expect(ChainReminders::morning(periodChain(ChainPeriod::Week, 2, '2026-10-11')))->toBeNull();
    });

    it('reminds a daily chain in the evening until today is marked', function () {
        $chain = Goal::factory()->chain('xx-')->create();
        $chain->chainDays()->whereDate('date', today())->delete();

        expect(ChainReminders::evening($chain->load('chainDays'))['text'])->toBe('bugün henüz işaretlenmedi');

        $chain->chainDays()->create(['date' => today(), 'state' => 'done']);

        expect(ChainReminders::evening($chain->load('chainDays')))->toBeNull()
            ->and(ChainReminders::evening(periodChain(ChainPeriod::Week, 2, '2026-09-28')))->toBeNull();
    });

    it('mails Kadir each reminder once, hidden chains included', function () {
        Mail::fake();
        config(['mail.contact_to' => 'kadir@example.test']);
        $this->travelTo(CarbonImmutable::parse('2026-10-08 08:00'));
        periodChain(ChainPeriod::Week, 2, '2026-09-28', attributes: ['visibility' => 'hidden']);

        $this->artisan('chains:remind')->assertSuccessful();
        $this->artisan('chains:remind')->assertSuccessful();

        Mail::assertSentCount(1);
        Mail::assertSent(ChainReminder::class, fn (ChainReminder $mail): bool => $mail->hasTo('kadir@example.test')
            && $mail->envelope()->subject === 'Spor: bu hafta 0/2, 4 günde 2 kez daha');
    });

    it('writes the reminder mail in both formats', function () {
        $this->travelTo(CarbonImmutable::parse('2026-10-10 08:00'));
        $chain = periodChain(ChainPeriod::Week, 2, '2026-09-28', ['2026-10-08' => 'done']);

        $mail = new ChainReminder([['chain' => $chain, ...ChainReminders::morning($chain)]]);

        expect($mail->render())->toContain('haftada 2')->toContain('2 günde 1 kez daha');
    });
});

describe('editor', function () {
    beforeEach(function () {
        $this->actingAs(User::factory()->admin()->create());
    });

    it('saves a weekly chain with its target', function () {
        Livewire::test('pages::admin.goals.edit', ['kind' => 'zincir'])
            ->set('form.title', 'Spor')
            ->set('form.chain_period', 'week')
            ->set('form.chain_target', 2)
            ->call('save')
            ->assertHasNoErrors();

        expect(Goal::sole())->chain_period->toBe(ChainPeriod::Week)->chain_target->toBe(2);
    });

    it('keeps the target within the period and at one for a daily chain', function () {
        Livewire::test('pages::admin.goals.edit', ['kind' => 'zincir'])
            ->set('form.title', 'Spor')
            ->set('form.chain_period', 'week')
            ->set('form.chain_target', 8)
            ->call('save')
            ->assertHasErrors(['form.chain_target'])
            ->set('form.chain_period', 'day')
            ->call('save')
            ->assertHasNoErrors();

        expect(Goal::sole()->chain_target)->toBe(1);
    });

    it('shows how the current week stands on the dashboard', function () {
        $this->travelTo(CarbonImmutable::parse('2026-10-08 08:00'));
        periodChain(ChainPeriod::Week, 2, '2026-09-28');

        Livewire::test('pages::admin.dashboard')->assertSee('bu hafta 0/2 · 4 günde 2 kez');
    });
});

describe('site and followers', function () {
    it('counts a weekly chain in weeks on the goals page', function () {
        $this->travelTo(CarbonImmutable::parse('2026-10-21 12:00'));
        periodChain(ChainPeriod::Week, 2, '2026-09-28', ['2026-09-29' => 'done', '2026-10-02' => 'done', '2026-10-06' => 'done', '2026-10-08' => 'done']);

        $this->get(route('goals.index'))->assertOk()->assertSee('haftada 2')->assertSee('en uzun seri: 2 hafta');
    });

    it('tells followers when a week broke a chain of weeks', function () {
        $this->travelTo(CarbonImmutable::parse('2026-10-19 00:30'));
        $chain = periodChain(ChainPeriod::Week, 1, '2026-09-21', ['2026-09-22' => 'done', '2026-09-29' => 'done', '2026-10-06' => 'done']);
        $member = User::factory()->member()->create();
        $member->follows()->create(['followable_type' => $chain->getMorphClass(), 'followable_id' => $chain->id]);

        $this->artisan('notifications:announce --chain-breaks');

        expect($member->notificationItems()->sole()->title)->toBe('Spor: zincir koptu (3 hafta sürdü)');
    });
});
