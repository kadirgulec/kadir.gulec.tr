<?php

use App\Enums\Permission;
use App\Models\Note;
use App\Models\Role;
use App\Models\Tag;
use App\Models\User;
use Illuminate\Support\Carbon;
use Livewire\Livewire;

/**
 * An admin panel user whose role cannot write notes.
 */
function editorWithoutNotes(): User
{
    $role = Role::create(['name' => 'editor', 'label' => 'Editör', 'guard_name' => 'web']);
    $role->givePermissionTo([Permission::AccessAdmin->value, Permission::ManagePosts->value]);

    $user = User::factory()->withTwoFactor()->create();
    $user->assignRole($role);

    return $user;
}

describe('editor', function () {
    beforeEach(function () {
        $this->actingAs(User::factory()->admin()->create());
    });

    it('publishes a new note right away under a new tag', function () {
        Carbon::setTestNow('2026-10-06 21:15');

        Livewire::test('pages::admin.notes.edit')
            ->set('form.body', "  Laravel'de `Model::preventLazyLoading()` N+1'i yakalıyor.  ")
            ->set('form.tagName', 'laravel')
            ->call('save')
            ->assertHasNoErrors()
            ->assertRedirect();

        $note = Note::sole();
        expect($note->body)->toBe("Laravel'de `Model::preventLazyLoading()` N+1'i yakalıyor.")
            ->and($note->tag->name)->toBe('laravel')
            ->and($note->published_at->format('Y-m-d H:i'))->toBe('2026-10-06 21:15');
    });

    it('asks for the text and the tag', function () {
        Livewire::test('pages::admin.notes.edit')
            ->set('form.body', '')
            ->set('form.tagName', '')
            ->call('save')
            ->assertHasErrors(['form.body' => 'not alanı zorunlu.', 'form.tagName' => 'etiket alanı zorunlu.']);

        expect(Note::count())->toBe(0);
    });

    it('saves a long note anyway, the counter only warns', function () {
        Livewire::test('pages::admin.notes.edit')
            ->set('form.body', str_repeat('a', 600))
            ->set('form.tagName', 'uzun')
            ->call('save')
            ->assertHasNoErrors();

        expect(Note::sole()->body)->toHaveLength(600);
    });

    it('moves a note to another tag and back to the drafts', function () {
        $note = Note::factory()->create();

        Livewire::test('pages::admin.notes.edit', ['note' => $note])
            ->set('form.tagName', 'yüzme')
            ->set('form.published_at', '')
            ->call('save')
            ->assertHasNoErrors();

        $note->refresh();
        expect($note->tag->name)->toBe('yüzme')
            ->and($note->published_at)->toBeNull();
    });

    it('deletes a note', function () {
        $note = Note::factory()->create();

        Livewire::test('pages::admin.notes.edit', ['note' => $note])
            ->call('delete')
            ->assertRedirect(route('admin.notes.index'));

        $this->assertModelMissing($note);
    });
});

describe('list', function () {
    it('filters notes by tag', function () {
        $this->actingAs(User::factory()->admin()->create());
        Note::factory()->for(Tag::factory()->state(['name' => 'yüzme']))->create(['body' => 'Başı aşağıda tut.']);
        Note::factory()->for(Tag::factory()->state(['name' => 'git']))->create(['body' => 'git switch - geri döner.']);

        Livewire::test('pages::admin.notes.index')
            ->set('tag', 'yuzme')
            ->assertSee('Başı aşağıda tut.')
            ->assertDontSee('git switch - geri döner.');
    });

    it('refuses the notes to an editor without the notes permission', function () {
        $this->actingAs(editorWithoutNotes())
            ->get('/admin/ogrendiklerim')
            ->assertForbidden();
    });
});

describe('quick note', function () {
    it('sticks a note onto the board from the dashboard and empties the card', function () {
        $this->actingAs(User::factory()->admin()->create());

        Livewire::test('pages::admin.dashboard')
            ->set('quickNote.body', 'Pilavı bezle demlendir.')
            ->set('quickNote.tagName', 'mutfak')
            ->call('addQuickNote')
            ->assertHasNoErrors()
            ->assertSet('quickNote.body', '')
            ->assertSet('quickNote.tagName', '');

        $note = Note::sole();
        expect($note->isPublished())->toBeTrue()
            ->and($note->tag->name)->toBe('mutfak');
    });

    it('does not let an editor without the notes permission add one', function () {
        $this->actingAs(editorWithoutNotes());

        Livewire::test('pages::admin.dashboard')
            ->assertDontSee('Hızlı not')
            ->set('quickNote.body', 'Gizlice.')
            ->set('quickNote.tagName', 'x')
            ->call('addQuickNote')
            ->assertForbidden();

        expect(Note::count())->toBe(0);
    });
});

describe('app shortcut', function () {
    it('offers "Yeni not" on the home screen icon only to whoever writes notes', function () {
        $visitorShortcuts = $this->get('/manifest.webmanifest')->json('shortcuts.*.name');
        $writerShortcuts = $this->actingAs(User::factory()->admin()->create())->get('/manifest.webmanifest')->json('shortcuts.0');

        expect($visitorShortcuts)->not->toContain('Yeni not')
            ->and($writerShortcuts)->toBe(['name' => 'Yeni not', 'url' => '/admin/ogrendiklerim/yeni']);
    });
});
