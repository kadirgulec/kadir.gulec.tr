<?php

use App\Enums\Permission;
use App\Models\Note;
use App\Models\Role;
use App\Models\Tag;
use App\Models\User;
use Illuminate\Support\Facades\Gate;
use Livewire\Livewire;

/**
 * An admin panel user whose role has only these content permissions.
 *
 * @param  list<Permission>  $permissions
 */
function contentEditor(array $permissions): User
{
    $role = Role::create(['name' => 'editor', 'label' => 'Editör', 'guard_name' => 'web']);
    $role->givePermissionTo([Permission::AccessAdmin->value, ...array_map(fn (Permission $permission): string => $permission->value, $permissions)]);

    $user = User::factory()->withTwoFactor()->create();
    $user->assignRole($role);

    return $user;
}

describe('access', function () {
    it('lets whoever manages posts or notes manage the shared tags', function (array $permissions, bool $allowed) {
        expect(Gate::forUser(contentEditor($permissions))->allows('manage-tags'))->toBe($allowed);
    })->with([
        'posts' => [[Permission::ManagePosts], true],
        'notes' => [[Permission::ManageNotes], true],
        'neither' => [[Permission::ManageGoals], false],
    ]);

    it('refuses the tag screen to an editor without posts or notes', function () {
        $this->actingAs(contentEditor([Permission::ManageGoals]))
            ->get('/admin/etiketler')
            ->assertForbidden();
    });

    it('moves the old tag screen address under the admin root', function () {
        $this->actingAs(User::factory()->admin()->create())
            ->get('/admin/yazilar/etiketler')
            ->assertRedirect('/admin/etiketler')
            ->assertStatus(301);
    });
});

describe('notes', function () {
    beforeEach(function () {
        $this->actingAs(User::factory()->admin()->create());
    });

    it('moves the notes of a merged tag to the target tag', function () {
        $note = Note::factory()->for(Tag::factory()->state(['name' => 'js']))->create();
        $target = Tag::factory()->create(['name' => 'javascript']);

        Livewire::test('pages::admin.tags.index')
            ->call('startMerge', $note->tag_id)
            ->set('mergeTarget', (string) $target->id)
            ->call('merge')
            ->assertHasNoErrors();

        expect($note->fresh()->tag_id)->toBe($target->id);
    });

    it('keeps a tag that still holds notes instead of deleting it', function () {
        $note = Note::factory()->create();

        Livewire::test('pages::admin.tags.index')
            ->call('delete', $note->tag_id)
            ->assertDispatched('toast', variant: 'danger');

        $this->assertModelExists($note->tag);
    });
});
