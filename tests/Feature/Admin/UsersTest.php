<?php

use App\Enums\Permission;
use App\Enums\SystemRole;
use App\Models\Role;
use App\Models\User;
use Livewire\Livewire;

/**
 * A moderator-like role that may manage users but is not an admin.
 */
function userManager(): User
{
    $role = Role::create(['name' => 'user-manager', 'label' => 'Kullanıcı yöneticisi', 'guard_name' => 'web']);
    $role->givePermissionTo([Permission::AccessAdmin->value, Permission::ManageUsers->value]);

    $user = User::factory()->withTwoFactor()->create();
    $user->assignRole($role);

    return $user;
}

describe('index', function () {
    it('lists users and filters them by name or e-mail', function () {
        User::factory()->member()->create(['name' => 'Ayşe Yılmaz']);
        User::factory()->member()->create(['name' => 'Mehmet Demir']);

        $this->actingAs(User::factory()->admin()->create());

        Livewire::test('pages::admin.users.index')
            ->assertSee('Ayşe Yılmaz')
            ->assertSee('Mehmet Demir')
            ->set('search', 'ayşe')
            ->assertSee('Ayşe Yılmaz')
            ->assertDontSee('Mehmet Demir');
    });

    it('filters users by role', function () {
        User::factory()->member()->create(['name' => 'Üye Kişi']);
        User::factory()->close()->create(['name' => 'Yakın Kişi']);

        $this->actingAs(User::factory()->admin()->create());

        Livewire::test('pages::admin.users.index')
            ->set('role', SystemRole::Close->value)
            ->assertSee('Yakın Kişi')
            ->assertDontSee('Üye Kişi');
    });

    it('forbids admin users without the users permission', function () {
        $role = Role::create(['name' => 'writer', 'label' => 'Yazar', 'guard_name' => 'web']);
        $role->givePermissionTo(Permission::AccessAdmin->value);
        $writer = User::factory()->withTwoFactor()->create();
        $writer->assignRole($role);

        $this->actingAs($writer)->get(route('admin.users.index'))->assertForbidden();
    });
});

describe('roles', function () {
    it('gives a user exactly the chosen roles', function () {
        $member = User::factory()->member()->create();
        $close = Role::findByName(SystemRole::Close->value);

        $this->actingAs(User::factory()->admin()->create());

        Livewire::test('pages::admin.users.show', ['user' => $member])
            ->set('roleIds', [$close->id])
            ->call('saveRoles')
            ->assertHasNoErrors();

        expect($member->fresh()->getRoleNames()->all())->toBe([SystemRole::Close->value]);
    });

    it('does not let the admin remove their own admin role', function () {
        $admin = User::factory()->admin()->create();
        User::factory()->admin()->create();

        $this->actingAs($admin);

        Livewire::test('pages::admin.users.show', ['user' => $admin])
            ->set('roleIds', [])
            ->call('saveRoles')
            ->assertHasErrors('roles');

        expect($admin->fresh()->isAdmin())->toBeTrue();
    });

    it('keeps the last admin an admin', function () {
        $admin = User::factory()->admin()->create();

        $this->actingAs(userManager());

        Livewire::test('pages::admin.users.show', ['user' => $admin])
            ->set('roleIds', [])
            ->call('saveRoles')
            ->assertHasErrors('roles');

        expect($admin->fresh()->isAdmin())->toBeTrue();
    });
});

describe('block', function () {
    it('blocks and unblocks a member', function () {
        $member = User::factory()->member()->create();

        $this->actingAs(User::factory()->admin()->create());

        $component = Livewire::test('pages::admin.users.show', ['user' => $member])->call('toggleBlock');
        expect($member->fresh()->isBlocked())->toBeTrue();

        $component->call('toggleBlock');
        expect($member->fresh()->isBlocked())->toBeFalse();
    });

    it('does not block the admin', function () {
        $admin = User::factory()->admin()->create();

        $this->actingAs($admin);

        Livewire::test('pages::admin.users.show', ['user' => $admin])
            ->call('toggleBlock')
            ->assertHasErrors('block');

        expect($admin->fresh()->isBlocked())->toBeFalse();
    });
});

describe('delete', function () {
    it('deletes a member', function () {
        $member = User::factory()->member()->create();

        $this->actingAs(User::factory()->admin()->create());

        Livewire::test('pages::admin.users.show', ['user' => $member])
            ->call('delete')
            ->assertRedirect(route('admin.users.index'));

        $this->assertModelMissing($member);
    });

    it('does not delete the acting user', function () {
        $admin = User::factory()->admin()->create();

        $this->actingAs($admin);

        Livewire::test('pages::admin.users.show', ['user' => $admin])
            ->call('delete')
            ->assertHasErrors('delete');

        $this->assertModelExists($admin);
    });

    it('does not delete the last admin', function () {
        $admin = User::factory()->admin()->create();

        $this->actingAs(userManager());

        Livewire::test('pages::admin.users.show', ['user' => $admin])
            ->call('delete')
            ->assertHasErrors('delete');

        $this->assertModelExists($admin);
    });
});
