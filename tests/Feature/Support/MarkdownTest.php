<?php

use App\Support\Markdown\Markdown;

it('turns footnotes into sidenotes where they are referenced', function () {
    $html = app(Markdown::class)->toHtml("Bir cümle[^1] burada.\n\n[^1]: Kenardaki **not**.", 'not-x');

    expect($html)
        ->toContain('<label for="not-x-1" class="sidenote-number">1</label>')
        ->toContain('<span class="sidenote"><span class="sidenote-key">1</span> Kenardaki <strong>not</strong>.</span>')
        ->not->toContain('footnote');
});

it('renders the highlighter and inline code with the notebook classes', function () {
    $html = app(Markdown::class)->toHtml('Bir ==vurgu== ve `kod`.');

    expect($html)->toContain('<mark class="marker">vurgu</mark>')->toContain('<code class="inline-code">kod</code>');
});

it('escapes raw HTML', function () {
    $html = app(Markdown::class)->toHtml("<script>alert(1)</script>\n\nMetin <img src=x onerror=alert(1)> burada.");

    expect($html)
        ->not->toContain('<script>')
        ->not->toContain('<img')
        ->toContain('&lt;script&gt;');
});

it('drops unsafe link targets', function () {
    $html = app(Markdown::class)->toHtml('Bir [link](javascript:alert(1)) ve [başka](data:text/html,x).');

    expect($html)->not->toContain('href="javascript')->not->toContain('href="data')->toContain('<a>link</a>');
});

it('makes an excerpt from the first paragraph without sidenotes', function () {
    $markdown = "İlk paragraf ==vurgulu==[^1] ve kısa.\n\nİkinci paragraf.\n\n[^1]: Not.";

    expect(app(Markdown::class)->excerpt($markdown))->toBe('İlk paragraf vurgulu ve kısa.');
});

it('cuts a long excerpt on a word boundary', function () {
    $excerpt = app(Markdown::class)->excerpt(str_repeat('kelime ', 60), 30);

    expect($excerpt)->toEndWith('…')->and(mb_strlen($excerpt))->toBeLessThanOrEqual(31);
});

it('counts reading minutes at 200 words per minute', function () {
    $markdown = app(Markdown::class);

    expect($markdown->readingMinutes('kısa'))->toBe(1)
        ->and($markdown->readingMinutes(str_repeat('çalışkan öğrenci ', 250)))->toBe(3);
});

it('opens links to other sites in a new tab, but not links within the site', function () {
    $html = app(Markdown::class)->toHtml('[Video](https://www.youtube.com/watch?v=x) ve [hedefler](/hedefler).');

    expect($html)
        ->toContain('<a rel="noopener" target="_blank" href="https://www.youtube.com/watch?v=x">Video</a>')
        ->toContain('<a href="/hedefler">hedefler</a>');
});
