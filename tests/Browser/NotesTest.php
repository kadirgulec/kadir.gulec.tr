<?php

use App\Models\Note;
use App\Models\Tag;
use App\Models\User;

/*
 * What only a real browser can tell about the notes: the stamp that appears
 * on scroll, the layering of links on a post-it, layout at phone width and
 * the live counter in the editor.
 */

/** The element a tap at the middle of the given element would really hit, as the closest link's path. */
const TAP_TARGET = <<<'JS'
    (selector) => {
        const box = document.querySelector(selector).getBoundingClientRect();
        const hit = document.elementFromPoint(box.x + box.width / 2, box.y + box.height / 2);
        return hit?.closest('a') ? new URL(hit.closest('a').href).pathname + new URL(hit.closest('a').href).search : null;
    }
JS;

/**
 * Scrolls to the given height and, a few frames later (the observer reports
 * asynchronously), reads the stamp's visibility. Done in one script: Pest's
 * wait() between two calls loses the scroll position.
 */
function stampVisibilityAt(int $scrollY): string
{
    return <<<JS
        new Promise(resolve => {
            window.scrollTo(0, {$scrollY});
            requestAnimationFrame(() => requestAnimationFrame(() => setTimeout(() => {
                resolve(getComputedStyle(document.querySelector('[data-home-stamp]')).visibility);
            }, 100)));
        })
        JS;
}

/** The note counter under the editor, found by its "/ 250" text. */
const NOTE_COUNTER = "[...document.querySelectorAll('p')].find(p => p.textContent.includes('/ 250'))";

function tapTarget(string $selector): string
{
    return '('.TAP_TARGET.')('.json_encode($selector).')';
}

function noteOn(string $tag, string $body): Note
{
    return Note::factory()->for(Tag::query()->firstOrCreate(['name' => $tag]))->create(['body' => $body, 'published_at' => now()->subDay()]);
}

describe('phone navigation', function () {
    it('presses the "kg" stamp into the corner once the signature scrolls away, and it leads home', function () {
        Note::factory()->count(8)->create();

        $page = visit(route('notes.index'))->on()->mobile();

        expect($page->script(stampVisibilityAt(0)))->toBe('hidden')
            ->and($page->script(stampVisibilityAt(1500)))->toBe('visible')
            ->and($page->script(stampVisibilityAt(0)))->toBe('hidden');

        $page->assertScript("new URL(document.querySelector('[data-home-stamp]').href).pathname", '/');
    });

    it('fits every tab name of the phone bar at 360 px', function () {
        visit(route('notes.index'))
            ->resize(360, 780)
            ->assertScript("[...document.querySelectorAll('nav.vt-bar li span:last-child')].every(label => label.scrollWidth <= label.clientWidth)", true)
            ->assertScript("document.querySelectorAll('nav.vt-bar li').length", 5);
    });
});

describe('post-it', function () {
    it('opens the note from anywhere on the card, but the tape and links in the text keep their own target', function () {
        $note = noteOn('yüzme', 'Başı aşağıda tut, [kaynak](https://example.test/yuzme) burada.');

        $page = visit(route('notes.index'));

        $page->assertScript(tapTarget('.post-it footer time'), '/ogrendiklerim/'.$note->id)
            ->assertScript(tapTarget('.post-it .washi'), '/ogrendiklerim?etiket=yuzme')
            ->assertScript(tapTarget('.post-it .prose-notebook a'), '/yuzme');

        $page->click('a[aria-label="Not #'.$note->id.'"]')->assertPathIs('/ogrendiklerim/'.$note->id);
    });

    it('keeps long code inside the post-it on a phone', function () {
        noteOn('javascript', '`structuredClone()` derin kopya için yerleşik; `JSON.parse(JSON.stringify(birNesneninTamamı))` hilesi tarihe karışabilir.');

        visit(route('notes.index'))
            ->resize(360, 780)
            ->assertScript(<<<'JS'
                (() => {
                    const card = document.querySelector('.post-it').getBoundingClientRect();
                    return [...document.querySelectorAll('.post-it .inline-code')].every(code => {
                        return [...code.getClientRects()].every(line => line.right <= card.right + 0.5);
                    });
                })()
                JS, true);
    });
});

describe('editor', function () {
    it('counts the note as it is typed, warns past 250 and 500, and still saves', function () {
        $this->actingAs(User::factory()->admin()->create());
        $page = visit(route('admin.notes.create'));

        $page->type('#field-form-body', str_repeat('a', 120))
            ->assertScript(NOTE_COUNTER.'.textContent.includes("120 / 250")', true)
            ->assertScript(NOTE_COUNTER.'.className.includes("text-zinc-500")', true);

        $page->type('#field-form-body', str_repeat('a', 260))
            ->assertSee('not uzuyor')
            ->assertScript(NOTE_COUNTER.'.className.includes("text-amber-600")', true);

        $page->type('#field-form-body', str_repeat('a', 510))
            ->assertSee('bu artık bir yazı olabilir')
            ->assertScript(NOTE_COUNTER.'.className.includes("text-red-600")', true)
            ->type('#field-form-tagname', 'uzun')
            ->press('Kaydet')
            ->assertSee('Not panoya yapıştı.');

        expect(Note::sole()->body)->toHaveLength(510);
    });
});
