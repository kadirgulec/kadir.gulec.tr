<?php

use App\Support\InlineMarkup;

it('escapes html written by the author', function () {
    $html = InlineMarkup::render('<script>alert(1)</script> ==<b>vurgu</b>==')->toHtml();

    expect($html)
        ->not->toContain('<script>')
        ->not->toContain('<b>')
        ->toContain('&lt;script&gt;')
        ->toContain('<mark class="marker">&lt;b&gt;vurgu&lt;/b&gt;</mark>');
});

it('turns the inline tokens into markup', function () {
    $html = InlineMarkup::render('==önemli== **kalın** `kod`')->toHtml();

    expect($html)->toBe('<mark class="marker">önemli</mark> <strong>kalın</strong> <code class="inline-code">kod</code>');
});

it('renders a sidenote with a toggle tied to a unique id', function () {
    $html = InlineMarkup::render('Metin.[^1]', ['1' => 'Kenar <notu>'], 'not-3')->toHtml();

    expect($html)
        ->toContain('<label for="not-3-1" class="sidenote-number">1</label>')
        ->toContain('<input type="checkbox" id="not-3-1" class="margin-toggle">')
        ->toContain('Kenar &lt;notu&gt;');
});

it('drops a sidenote marker without a matching note', function () {
    $html = InlineMarkup::render('Metin.[^9]')->toHtml();

    expect($html)->toBe('Metin.');
});
