<?php

use App\Enums\Permission;
use App\Enums\ReadingStatus;
use App\Models\Book;
use App\Models\Goal;
use App\Models\Reading;
use App\Models\Role;
use App\Models\User;
use App\Models\Watchable;
use Illuminate\Http\Client\Request;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Storage;
use Livewire\Livewire;

beforeEach(function () {
    Storage::fake('public');
    $this->actingAs(User::factory()->admin()->create());
});

/**
 * Open Library answers for one work (Kürk Mantolu Madonna) and its cover.
 */
function fakeOpenLibrary(): void
{
    Http::fake([
        'openlibrary.org/search.json*' => Http::response(['docs' => [
            ['key' => '/works/OL24327596W', 'title' => 'Kürk Mantolu Madonna', 'author_name' => ['Sabahattin Ali'], 'first_publish_year' => 1943, 'cover_i' => 10848013, 'language' => ['tur', 'eng']],
        ]]),
        'openlibrary.org/works/OL24327596W.json' => Http::response(['description' => ['type' => '/type/text', 'value' => 'Raif Efendi\'nin defteri.']]),
        'covers.openlibrary.org/*' => Http::response(UploadedFile::fake()->image('cover.jpg', 300, 450)->getContent(), 200, ['Content-Type' => 'image/jpeg']),
    ]);
}

function editorWithBooksOnly(): User
{
    $role = Role::create(['name' => 'book-keeper', 'label' => 'Kitapçı', 'guard_name' => 'web']);
    $role->givePermissionTo([Permission::AccessAdmin->value, Permission::ManageBooks->value]);

    $user = User::factory()->withTwoFactor()->create();
    $user->assignRole($role);

    return $user;
}

it('keeps members out of the book screens', function () {
    $this->actingAs(User::factory()->member()->create())
        ->get(route('admin.books.index'))
        ->assertNotFound();
});

it('lets a book keeper in, but not into the films', function () {
    $this->actingAs(editorWithBooksOnly());

    $this->get(route('admin.books.index'))->assertOk();
    $this->get(route('admin.watched.index'))->assertForbidden();
});

describe('adding a book', function () {
    it('searches by ISBN and imports the work as a draft with its description, cover and accent', function () {
        fakeOpenLibrary();

        Livewire::test('pages::admin.books.create')
            ->set('query', '978-975-363-802-9')
            ->call('search')
            ->assertSee('Kürk Mantolu Madonna')
            ->call('import', 0)
            ->assertRedirect();

        $book = Book::sole();
        expect($book->only(['title', 'author', 'year', 'isbn', 'language', 'overview', 'open_library_key']))->toBe([
            'title' => 'Kürk Mantolu Madonna', 'author' => 'Sabahattin Ali', 'year' => 1943, 'isbn' => '9789753638029',
            'language' => 'Türkçe', 'overview' => 'Raif Efendi\'nin defteri.', 'open_library_key' => '/works/OL24327596W',
        ])
            ->and($book->cover_path)->toStartWith('covers/')
            ->and($book->accent)->toStartWith('#')
            ->and($book->published_at)->toBeNull();

        Http::assertSent(fn (Request $request) => str_contains($request->url(), 'search.json') && $request['isbn'] === '9789753638029');
    });

    it('opens the existing book instead of importing a work twice', function () {
        fakeOpenLibrary();
        $existing = Book::factory()->create(['open_library_key' => '/works/OL24327596W']);

        Livewire::test('pages::admin.books.create')
            ->set('query', 'kürk mantolu')
            ->call('search')
            ->call('import', 0)
            ->assertRedirect(route('admin.books.edit', $existing));

        expect(Book::query()->count())->toBe(1);
    });

    it('puts a search result straight onto the end of the reading list', function () {
        fakeOpenLibrary();
        Book::factory()->onReadingList()->create(['title' => 'Önceki']);

        Livewire::test('pages::admin.books.create')
            ->set('query', 'kürk mantolu')
            ->call('search')
            ->call('addToReadingList', 0)
            ->assertRedirect(route('admin.books.reading-list'));

        expect(Book::query()->onReadingList()->pluck('title')->all())->toBe(['Önceki', 'Kürk Mantolu Madonna']);
    });

    it('says so when Open Library does not answer', function () {
        Http::fake(['openlibrary.org/*' => Http::response(null, 503)]);

        Livewire::test('pages::admin.books.create')
            ->set('query', 'kürk mantolu')
            ->call('search')
            ->assertHasErrors(['query'])
            ->assertSet('results', []);
    });

    it('creates a book by hand with its title and author', function () {
        Livewire::test('pages::admin.books.create')
            ->set('manualTitle', 'Bir Defter')
            ->call('createManually')
            ->assertHasErrors(['manualAuthor'])
            ->set('manualAuthor', 'Kadir Güleç')
            ->call('createManually')
            ->assertRedirect();

        expect(Book::sole()->only(['title', 'author', 'language']))->toBe(['title' => 'Bir Defter', 'author' => 'Kadir Güleç', 'language' => 'Türkçe'])
            ->and(Book::sole()->cover_colors)->toHaveCount(2);
    });
});

describe('editing a book', function () {
    it('saves the facts and the take on the book', function () {
        $book = Book::factory()->draft()->create();

        Livewire::test('pages::admin.books.edit', ['book' => $book])
            ->set('form.title', 'Körlük')
            ->set('form.author', 'José Saramago')
            ->set('form.translator', 'Aykut Derman')
            ->set('form.format', 'print')
            ->set('form.rating', '8.5')
            ->set('form.review', ":::alıntı Doktorun karısı\nBir cümle.\n:::")
            ->call('save')
            ->assertHasNoErrors();

        $book->refresh();
        expect($book->only(['title', 'translator', 'rating']))->toBe(['title' => 'Körlük', 'translator' => 'Aykut Derman', 'rating' => 8.5])
            ->and($book->format->value)->toBe('print')
            ->and((string) $book->review_html)->toContain('Doktorun karısı');
    });

    it('starts a reading, which takes the book off the reading list', function () {
        $book = Book::factory()->onReadingList()->create();

        Livewire::test('pages::admin.books.edit', ['book' => $book])
            ->set('startedOn', '2026-09-28')
            ->call('startReading')
            ->assertHasNoErrors();

        $reading = $book->readings()->sole();
        expect($reading->status)->toBe(ReadingStatus::Reading)
            ->and($reading->started_on->toDateString())->toBe('2026-09-28')
            ->and($book->fresh()->isOnReadingList())->toBeFalse();
    });

    it('turns pages into a percentage and ends a finished reading today', function () {
        $this->travelTo('2026-10-13');
        $book = Book::factory()->create();
        $reading = Reading::factory()->reading(10)->for($book)->create(['started_on' => '2026-09-01']);

        $component = Livewire::test('pages::admin.books.edit', ['book' => $book])
            ->set("readingRows.{$reading->id}.progress", '143/671')
            ->call('saveReadings')
            ->assertHasNoErrors();

        expect($reading->fresh()->progress)->toBe(21);

        $component->set("readingRows.{$reading->id}.status", 'finished')
            ->call('saveReadings')
            ->assertHasNoErrors();

        expect($reading->fresh()->only(['progress']))->toBe(['progress' => 100])
            ->and($reading->fresh()->finished_on->toDateString())->toBe('2026-10-13');
    });

    it('refuses a progress it cannot read and an end before the start', function () {
        $book = Book::factory()->create();
        $reading = Reading::factory()->for($book)->create(['started_on' => '2026-09-01', 'finished_on' => '2026-09-20']);

        Livewire::test('pages::admin.books.edit', ['book' => $book])
            ->set("readingRows.{$reading->id}.progress", 'yarısı')
            ->call('saveReadings')
            ->assertHasErrors(["readingRows.{$reading->id}.progress"])
            ->set("readingRows.{$reading->id}.progress", '')
            ->set("readingRows.{$reading->id}.finished_on", '2026-08-01')
            ->call('saveReadings')
            ->assertHasErrors(["readingRows.{$reading->id}.finished_on"]);

        expect($reading->fresh()->finished_on->toDateString())->toBe('2026-09-20');
    });
});

describe('quotes', function () {
    it('adds, edits, hides, orders and deletes the quotes of a book', function () {
        $book = Book::factory()->create();

        $component = Livewire::test('admin.quotes', ['type' => 'book', 'id' => $book->id])
            ->set('body', 'Birinci.')
            ->set('location', '%34')
            ->call('save')
            ->set('body', 'İkinci.')
            ->set('saidBy', 'Raif Efendi')
            ->call('save')
            ->assertSee('— Raif Efendi');

        [$first, $second] = $book->quotes()->get()->all();

        $component->call('edit', $first->id)->set('body', 'Birinci, düzeltildi.')->call('save')
            ->call('toggle', $second->id)
            ->call('sort', $second->id, 0);

        expect($book->quotes()->pluck('body')->all())->toBe(['İkinci.', 'Birinci, düzeltildi.'])
            ->and($second->fresh()->isPublished())->toBeFalse()
            ->and($first->fresh()->location)->toBe('%34');

        $component->call('delete', $first->id);

        expect($book->quotes()->count())->toBe(1);
    });

    it('collects quotes for a film on its edit screen too', function () {
        $film = Watchable::factory()->create();

        Livewire::test('pages::admin.watched.edit', ['watchable' => $film])->assertSeeLivewire('admin.quotes');
        Livewire::test('admin.quotes', ['type' => 'watchable', 'id' => $film->id])
            ->set('body', 'Hayat bu.')
            ->set('location', '01:42')
            ->call('save');

        expect($film->quotes()->sole()->body)->toBe('Hayat bu.');
    });

    it('keeps the quotes of a kind to those who manage it', function () {
        $film = Watchable::factory()->create();
        $this->actingAs(editorWithBooksOnly());

        Livewire::test('admin.quotes', ['type' => 'watchable', 'id' => $film->id])->assertForbidden();
    });
});

it('reorders the reading list and keeps a note on it', function () {
    [$first, $second] = Book::factory()->count(2)->onReadingList()->create();

    Livewire::test('pages::admin.books.reading-list')
        ->call('sort', $second->id, 0)
        ->call('editNote', $first->id)
        ->set('note', 'Ali önerdi')
        ->call('saveNote')
        ->assertSee('Ali önerdi');

    expect(Book::query()->onReadingList()->pluck('id')->all())->toBe([$second->id, $first->id]);
});

describe('the Okuyorum card on Masa', function () {
    it('updates the progress, takes a quote and finishes a book', function () {
        $this->travelTo('2026-10-13');
        $book = Book::factory()->create(['title' => 'Saatleri Ayarlama Enstitüsü']);
        $reading = Reading::factory()->paused(30)->for($book)->create(['started_on' => '2026-09-28']);
        Reading::factory()->for(Book::factory()->create(['title' => 'Bitmiş Kitap']))->create();

        $component = Livewire::test('pages::admin.dashboard')
            ->assertSee('Saatleri Ayarlama Enstitüsü')
            ->assertDontSee('Bitmiş Kitap')
            ->set("progressInputs.{$book->id}", '%43')
            ->call('updateProgress', $book->id)
            ->assertHasNoErrors();

        expect($reading->fresh()->only(['progress']))->toBe(['progress' => 43])
            ->and($reading->fresh()->status)->toBe(ReadingStatus::Reading);

        $component->call('openQuote', $book->id)
            ->set('quoteBody', 'Her şey saatlerle başladı.')
            ->set('quoteLocation', '%43')
            ->call('addQuote')
            ->call('finishBook', $book->id)
            ->assertDontSee('Saatleri Ayarlama Enstitüsü');

        expect($book->quotes()->sole()->only(['body', 'location']))->toBe(['body' => 'Her şey saatlerle başladı.', 'location' => '%43'])
            ->and($reading->fresh()->status)->toBe(ReadingStatus::Finished)
            ->and($reading->fresh()->finished_on->toDateString())->toBe('2026-10-13');
    });

    it('keeps the +1 away from a goal counted from Okuduklarım', function () {
        $counted = Goal::factory()->countingBooks()->create(['title' => '20 kitap oku', 'target' => 20]);
        Goal::factory()->yearly()->create(['title' => '500 km koş', 'target' => 500]);

        Livewire::test('pages::admin.dashboard')
            ->assertSeeInOrder(['20 kitap oku', 'Okuduklarım\'dan sayılıyor', '500 km koş'])
            ->call('addOne', $counted->id)
            ->assertNotFound();

        expect($counted->current())->toBe(0);
    });

    it('offers to copy last year\'s book goal in January only', function () {
        Goal::factory()->countingBooks(2026)->create(['title' => '20 kitap oku', 'target' => 20]);

        $this->travelTo('2027-02-01');
        Livewire::test('pages::admin.dashboard')->assertDontSee('kitap hedefin yok');

        $this->travelTo('2027-01-05');
        Livewire::test('pages::admin.dashboard')
            ->assertSee('2027 için kitap hedefin yok. Geçen yılki (20 kitap) kopyalansın mı?')
            ->call('copyReadingGoal')
            ->assertDontSee('kitap hedefin yok');

        expect(Goal::query()->countingBooksIn(2027)->sole()->only(['title', 'target']))->toBe(['title' => '20 kitap oku', 'target' => 20]);
    });

    it('is not on the desk of someone who does not keep the books', function () {
        $role = Role::create(['name' => 'goal-keeper', 'label' => 'Hedefçi', 'guard_name' => 'web']);
        $role->givePermissionTo([Permission::AccessAdmin->value, Permission::ManageGoals->value]);
        $user = User::factory()->withTwoFactor()->create();
        $user->assignRole($role);
        Reading::factory()->reading()->create();

        Livewire::actingAs($user)->test('pages::admin.dashboard')->assertDontSee('Okuyorum');
    });
});

it('offers "Kitap ekle" on the home screen icon to whoever keeps the books', function () {
    expect($this->get('/manifest.webmanifest')->json('shortcuts.*.name'))->toContain('Kitap ekle');

    auth()->logout();

    expect($this->get('/manifest.webmanifest')->json('shortcuts.*.name'))->not->toContain('Kitap ekle');
});
