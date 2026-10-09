<?php

use App\Enums\ChainPeriod;
use App\Enums\GoalMeasure;
use App\Models\Goal;
use App\Models\GoalProgress;
use App\Models\Note;
use App\Models\PageView;
use App\Models\Post;
use App\Models\Viewing;
use App\Models\Watchable;
use App\Support\Reviews\MonthlyReviewStats;
use Carbon\CarbonImmutable;

beforeEach(function () {
    // The review of October 2026 is made on 1 November.
    $this->travelTo(CarbonImmutable::parse('2026-11-01 00:15'));
});

/**
 * A chain started on $startedOn with the given marks (date => done|excused).
 *
 * @param  array<string, string>  $marks
 */
function reviewChain(string $startedOn, array $marks, ChainPeriod $period = ChainPeriod::Day, int $target = 1): Goal
{
    $chain = Goal::factory()->chain('')->per($period, $target)->create(['started_on' => $startedOn]);

    foreach ($marks as $date => $state) {
        $chain->chainDays()->create(['date' => $date, 'state' => $state]);
    }

    return $chain;
}

/**
 * @return array<string, mixed>
 */
function octoberStats(): array
{
    return app(MonthlyReviewStats::class)->for(CarbonImmutable::parse('2026-10-01'));
}

it('counts the days and links of a daily chain within the month only', function () {
    $chain = reviewChain('2026-10-28', [
        '2026-10-28' => 'done',
        '2026-10-29' => 'excused',
        // 30 October missed
        '2026-10-31' => 'done',
        '2026-11-01' => 'done',
    ]);

    expect(octoberStats()['chains'])->toBe([[
        'goal_id' => $chain->id,
        'period' => 'day',
        'target' => 1,
        'doneDays' => 2,
        'excusedDays' => 1,
        'links' => 4,
        'held' => 2,
        'excused' => 1,
        'successRate' => 67,
        'bestStreak' => 1,
        'record' => null,
    ]]);
});

it('puts a weekly link into the month its week ends in', function () {
    // 2026-09-28 is a Monday: that week ends on 4 October, the week of 26 October on 1 November.
    reviewChain('2026-09-28', [
        '2026-09-29' => 'done', '2026-09-30' => 'done',
        '2026-10-27' => 'done', '2026-10-28' => 'done',
    ], ChainPeriod::Week, 2);

    $chain = octoberStats()['chains'][0];

    expect($chain['links'])->toBe(4)
        ->and($chain['held'])->toBe(1)
        ->and($chain['doneDays'])->toBe(2);
});

it('leaves out hidden chains and chains that did not run in the month', function () {
    reviewChain('2026-10-01', ['2026-10-01' => 'done'])->update(['visibility' => 'hidden']);
    reviewChain('2026-11-01', ['2026-11-01' => 'done']);
    reviewChain('2026-08-01', ['2026-08-01' => 'done'])->update(['ended_on' => '2026-09-30']);

    expect(octoberStats()['chains'])->toBe([]);
});

it('keeps a censored chain by id', function () {
    $chain = reviewChain('2026-10-30', ['2026-10-30' => 'done']);
    $chain->update(['visibility' => 'censored']);

    expect(octoberStats()['chains'][0]['goal_id'])->toBe($chain->id);
});

it('reports a new record only when it beats a real earlier one', function (int $daysBefore, ?int $record) {
    $marks = [];

    // A run of $daysBefore days ending 1 September, then 10 days in October.
    for ($day = $daysBefore - 1; $day >= 0; $day--) {
        $marks[CarbonImmutable::parse('2026-09-01')->subDays($day)->toDateString()] = 'done';
    }

    for ($day = 1; $day <= 10; $day++) {
        $marks[sprintf('2026-10-%02d', $day)] = 'done';
    }

    reviewChain(array_key_first($marks), $marks);

    expect(octoberStats()['chains'][0]['record'])->toBe($record);
})->with([
    'after a week-long run' => [7, 10],
    'after a run shorter than a week' => [6, null],
    'not beating the earlier run' => [12, null],
]);

it('adds up what the month brought to a numeric goal and where it stood at the end', function () {
    $goal = Goal::factory()->yearly(GoalMeasure::Numeric, 2026)->create(['target' => 12, 'unit' => 'kitap']);
    GoalProgress::factory()->for($goal)->create(['date' => '2026-03-10', 'amount' => 6]);
    GoalProgress::factory()->for($goal)->create(['date' => '2026-10-05', 'amount' => 2]);
    GoalProgress::factory()->for($goal)->create(['date' => '2026-11-01', 'amount' => 3]);

    expect(octoberStats()['yearly'])->toBe([[
        'goal_id' => $goal->id,
        'unit' => 'kitap',
        'target' => 12,
        'added' => 2,
        'current' => 8,
        // 8 / 12 by the end of October (83 % of the year) is well behind.
        'pace' => 'behind',
    ]]);
});

it('only counts visible numeric goals of the month\'s year', function () {
    Goal::factory()->yearly(GoalMeasure::Numeric, 2026)->hidden()->create();
    Goal::factory()->yearly(GoalMeasure::Numeric, 2025)->create();
    Goal::factory()->yearly(GoalMeasure::Milestones, 2026)->create();

    expect(octoberStats()['yearly'])->toBe([]);
});

it('counts what was published in the month', function () {
    Post::factory()->create(['published_at' => '2026-10-03 09:00']);
    Post::factory()->create(['published_at' => '2026-09-30 23:00']);
    Post::factory()->draft()->create();
    Note::factory()->count(2)->create(['published_at' => '2026-10-20 10:00']);
    Viewing::factory()->create(['watched_on' => '2026-10-12']);
    Viewing::factory()->for(Watchable::factory()->draft())->create(['watched_on' => '2026-10-12']);

    expect(octoberStats()['published'])->toBe(['posts' => 1, 'notes' => 2, 'viewings' => 1]);
});

it('counts views and visits and finds the most read published post', function () {
    $read = Post::factory()->create(['slug' => 'okunan', 'published_at' => '2026-09-01']);
    Post::factory()->draft()->create(['slug' => 'taslak']);

    $view = fn (string $path, string $hash, string $at = '2026-10-10 12:00') => PageView::factory()->create(['path' => $path, 'visitor_hash' => $hash, 'created_at' => $at]);
    $view('/yazilar/taslak', 'aaaa', '2026-10-01 08:00');
    $view('/yazilar/taslak', 'bbbb');
    $view('/yazilar/taslak', 'cccc');
    $view('/yazilar/okunan', 'aaaa');
    $view('/yazilar/okunan', 'bbbb');
    $view('/hedefler', 'aaaa');
    $view('/yazilar/okunan', 'dddd', '2026-11-01 00:05');

    expect(octoberStats()['visitors'])->toBe([
        'views' => 6,
        'visits' => 3,
        'topPost' => ['post_id' => $read->id, 'views' => 2],
    ]);
});

it('has no most read post in a month without post views', function () {
    expect(octoberStats()['visitors'])->toBe(['views' => 0, 'visits' => 0, 'topPost' => null]);
});
