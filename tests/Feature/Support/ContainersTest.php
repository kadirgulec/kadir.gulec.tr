<?php

use App\Support\Markdown\Markdown;

it('crosses out a spoiler with marker and keeps its Markdown', function () {
    $html = app(Markdown::class)->toHtml(":::spoiler\nSonunda **baba** hayatta.\n:::");

    expect($html)
        ->toContain('data-spoiler')
        ->toContain('<span class="spoiler-text">Sonunda <strong>baba</strong> hayatta.</span>')
        ->toContain('SPOİLER');
});

it('puts a quote on a post-it with who said it', function () {
    $html = app(Markdown::class)->toHtml(":::replik Tyler Durden\nİlk kural…\n:::");

    expect($html)->toContain('“İlk kural…”')->toContain('— Tyler Durden');
});

it('escapes the speaker name', function () {
    $html = app(Markdown::class)->toHtml(":::replik <b>x</b>\nmetin\n:::");

    expect($html)->toContain('— &lt;b&gt;x&lt;/b&gt;')->not->toContain('<b>x</b>');
});

it('leaves unknown containers as plain text', function () {
    expect(app(Markdown::class)->toHtml(":::kutu\nmetin\n:::"))->toContain(':::kutu');
});
