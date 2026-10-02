<?php

use App\Enums\GoalPace;
use Carbon\CarbonImmutable;

it('compares progress with the share of the year that has passed', function (float $current, float $target, string $date, GoalPace $expected) {
    $pace = GoalPace::evaluate($current, $target, CarbonImmutable::parse($date));

    expect($pace)->toBe($expected);
})->with([
    // 2 October is day 275 of 365, so about 75 % of the year is gone.
    'clearly ahead' => [10, 12, '2026-10-02', GoalPace::Ahead],
    'close to the calendar' => [362, 500, '2026-10-02', GoalPace::OnTrack],
    'far behind' => [9, 24, '2026-10-02', GoalPace::Behind],
    'reached early' => [12, 12, '2026-03-01', GoalPace::Ahead],
    'nothing yet in early January' => [0, 12, '2026-01-02', GoalPace::OnTrack],
]);

it('counts 366 days in a leap year', function () {
    $share = GoalPace::yearShare(CarbonImmutable::parse('2028-12-31'));

    expect($share)->toBe(1.0);
});
