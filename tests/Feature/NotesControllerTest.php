<?php

use App\Models\Book;
use App\Models\Note;
use App\Models\Post;
use App\Models\Tag;
use App\Models\User;
use App\Models\Watchable;
use App\Support\Content\NoteContent;

function noteTagged(string $tag, string $body, ?string $publishedAt = '2026-09-01'): Note
{
    return Note::factory()->for(Tag::query()->firstOrCreate(['name' => $tag]))->create(['body' => $body, 'published_at' => $publishedAt]);
}

describe('board', function () {
    it('pins published notes newest first and keeps drafts and scheduled notes off', function () {
        noteTagged('git', 'Eski not.', '2026-08-01');
        noteTagged('git', 'Yeni not.', '2026-09-01');
        noteTagged('git', 'Taslak not.', null);
        noteTagged('git', 'Yarının notu.', now()->addDay()->toDateTimeString());

        $this->get(route('notes.index'))
            ->assertOk()
            ->assertSeeTextInOrder(['Yeni not.', 'Eski not.'])
            ->assertDontSeeText('Taslak not.')
            ->assertDontSeeText('Yarının notu.');
    });

    it('filters the board by tag', function () {
        noteTagged('yüzme', 'Başı aşağıda tut.');
        noteTagged('git', 'git switch - geri döner.');

        $this->get(route('notes.index', ['etiket' => 'yuzme']))
            ->assertSeeText('#yüzme etiketli notlar')
            ->assertSeeText('Başı aşağıda tut.')
            ->assertDontSeeText('git switch - geri döner.');
    });

    it('returns 404 for a tag without published notes', function () {
        noteTagged('mutfak', 'Taslak tarif.', null);

        $this->get(route('notes.index', ['etiket' => 'mutfak']))->assertNotFound();
    });

    it('shows a page of notes at a time and keeps the tag filter in the page links', function () {
        $tag = Tag::factory()->create(['name' => 'git']);
        Note::factory()->for($tag)->count(NoteContent::PER_PAGE + 1)->sequence(fn ($sequence) => ['published_at' => now()->subDays($sequence->index + 1)])->create();
        Note::query()->oldest('published_at')->first()->update(['body' => 'En eski not.']);

        $this->get(route('notes.index', ['etiket' => 'git']))
            ->assertDontSeeText('En eski not.')
            ->assertSee(route('notes.index', ['etiket' => 'git', 'sayfa' => 2]));

        $this->get(route('notes.index', ['etiket' => 'git', 'sayfa' => 2]))
            ->assertSeeText('En eski not.')
            ->assertSeeText('sayfa 2 / 2');
    });

    it('gives a later page its own address and has no page past the last one', function () {
        Note::factory()->count(NoteContent::PER_PAGE + 1)->create();

        $this->get(route('notes.index'))
            ->assertSee('<link rel="canonical" href="'.route('notes.index').'" />', false);

        $this->get(route('notes.index', ['sayfa' => 2]))
            ->assertSee('<link rel="canonical" href="'.route('notes.index', ['sayfa' => 2]).'" />', false)
            ->assertSee('<title>Panom · öğrendiklerim · sayfa 2 · Kadir Gülec</title>', false);

        $this->get(route('notes.index', ['sayfa' => 3]))->assertNotFound();
    });
});

describe('note page', function () {
    it('shows a note with its neighbours, other notes of its tag and posts on the topic', function () {
        $older = noteTagged('laravel', 'Daha eski not.', '2026-08-01');
        $note = noteTagged('laravel', 'Bu not.', '2026-09-01');
        $newer = noteTagged('git', 'Daha yeni not.', '2026-09-10');
        $post = Post::factory()->create(['title' => 'Laravel üzerine bir yazı']);
        $post->syncTagNames(['laravel']);

        $this->get(route('notes.show', $note->id))
            ->assertOk()
            ->assertSee('<title>Bu not. · Kadir Gülec</title>', false)
            ->assertSee('<h1 class="sr-only">Bu not.</h1>', false)
            ->assertSeeText('Daha eski not.')
            ->assertSeeText('Daha yeni not.')
            ->assertSeeText('#laravel etiketli başka notlar')
            ->assertSeeText('Laravel üzerine bir yazı');
    });

    it('hides a draft from visitors and shows it with the banner to whoever manages notes', function () {
        $draft = noteTagged('git', 'Yarım not.', null);

        $this->get(route('notes.show', $draft->id))->assertNotFound();

        $this->actingAs(User::factory()->admin()->create())
            ->get(route('notes.show', $draft->id))
            ->assertOk()
            ->assertSeeText('Taslak · sadece sen görüyorsun');
    });

    it('names its source on the post-it, linked only while the source is public', function () {
        $book = Book::factory()->create(['title' => 'Körlük', 'slug' => 'korluk']);
        $draftFilm = Watchable::factory()->draft()->create(['title' => 'Taslak Film']);
        $fromBook = Note::factory()->create(['source_type' => 'book', 'source_id' => $book->id]);
        $fromFilm = Note::factory()->create(['source_type' => 'watchable', 'source_id' => $draftFilm->id]);

        $this->get(route('notes.show', $fromBook))->assertSeeText('kaynak: Körlük')->assertSee('/okuduklarim/korluk');
        $this->get(route('notes.show', $fromFilm))->assertSeeText('kaynak: Taslak Film')->assertDontSee($draftFilm->publicPath());
        $this->get(route('notes.index'))->assertSeeText('kaynak: Körlük');
    });

    it('keeps a note when its source is deleted, only without the source', function () {
        $book = Book::factory()->create();
        $note = Note::factory()->create(['source_type' => 'book', 'source_id' => $book->id]);

        $book->delete();

        expect($note->fresh()->only(['source_type', 'source_id']))->toBe(['source_type' => null, 'source_id' => null]);
    });

    it('returns 404 for an address that is not a note number', function () {
        $this->get('/ogrendiklerim/bir-not')->assertNotFound();
    });
});

describe('elsewhere on the site', function () {
    it('shows small notes of its tags under a post', function () {
        $post = Post::factory()->create();
        $post->syncTagNames(['css']);
        noteTagged('css', 'text-wrap: balance başlıkları dengeler.');
        noteTagged('git', 'Başka konuda bir not.');

        $this->get(route('posts.show', $post->slug))
            ->assertSeeText('Bu konuda küçük notlar')
            ->assertSeeText('text-wrap: balance başlıkları dengeler.')
            ->assertDontSeeText('Başka konuda bir not.');
    });

    it('puts the latest note on the cover', function () {
        noteTagged('git', 'Eski kapak notu.', '2026-08-01');
        noteTagged('git', 'Son kapak notu.', '2026-09-01');

        $this->get(route('home'))
            ->assertSeeText('son öğrendiğim')
            ->assertSeeText('Son kapak notu.')
            ->assertDontSeeText('Eski kapak notu.');
    });
});
