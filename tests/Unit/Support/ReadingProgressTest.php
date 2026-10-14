<?php

use App\Support\ReadingProgress;

it('reads a percentage the way the Kindle shows it', function (string $input, int $expected) {
    expect(ReadingProgress::parse($input))->toBe($expected);
})->with([
    'plain' => ['43', 43],
    'leading sign' => ['%43', 43],
    'trailing sign' => ['43%', 43],
    'spaces' => [' % 7 ', 7],
    'done' => ['100', 100],
]);

it('turns the pages of a printed book into a percentage', function () {
    expect(ReadingProgress::parse('143/671'))->toBe(21)
        ->and(ReadingProgress::parse('671 / 671'))->toBe(100);
});

it('rejects what is not a progress', function (string $input) {
    expect(ReadingProgress::parse($input))->toBeNull();
})->with(['', 'yarısı', '101', '700/671', '5/0', '4.5']);
