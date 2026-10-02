<?php

use App\Support\ChainStats;

it('counts the done days since the last missed day', function () {
    $streak = ChainStats::currentStreak(['done', 'missed', 'done', 'done', 'done']);

    expect($streak)->toBe(3);
});

it('does not let an excused day break or extend a streak', function () {
    $days = ['missed', 'done', 'excused', 'done'];

    expect(ChainStats::currentStreak($days))->toBe(2)
        ->and(ChainStats::bestStreak($days))->toBe(2);
});

it('finds the longest run, even when it is not the current one', function () {
    $best = ChainStats::bestStreak(['done', 'done', 'done', 'missed', 'done']);

    expect($best)->toBe(3);
});

it('leaves excused days out of the success rate', function () {
    $rate = ChainStats::successRate(['done', 'done', 'done', 'missed', 'excused']);

    expect($rate)->toBe(75);
});

it('returns zero for a chain without days', function () {
    expect(ChainStats::currentStreak([]))->toBe(0)
        ->and(ChainStats::bestStreak([]))->toBe(0)
        ->and(ChainStats::successRate([]))->toBe(0);
});
