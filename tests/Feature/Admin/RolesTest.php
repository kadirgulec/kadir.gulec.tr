<?php

use App\Enums\Permission;
use App\Enums\SystemRole;
use App\Models\Role;
use App\Models\User;
use Livewire\Livewire;

beforeEach(function () {
    $this->actingAs(User::factory()->admin()->create());
});

it('creates a role whose machine name comes from the label', function () {
    Livewire::test('pages::admin.roles.index')
        ->set('label', 'Yorum Moderatörü')
        ->call('create')
        ->assertHasNoErrors();

    $role = Role::findByName('yorum-moderatoru');
    expect($role->label)->toBe('Yorum Moderatörü');
});

it('rejects a second role with the same name', function () {
    Livewire::test('pages::admin.roles.index')
        ->set('label', 'Üye')
        ->call('create')
        ->assertHasErrors(['label']);
});

it('saves the label and permissions of a role without changing its machine name', function () {
    $member = Role::findByName(SystemRole::Member->value);

    Livewire::test('pages::admin.roles.edit', ['role' => $member])
        ->set('label', 'Okur')
        ->set('permissions', [Permission::Follow->value])
        ->call('save')
        ->assertHasNoErrors();

    $member->refresh();
    expect($member->name)->toBe(SystemRole::Member->value)
        ->and($member->label)->toBe('Okur')
        ->and($member->permissions->pluck('name')->all())->toBe([Permission::Follow->value]);
});

it('rejects a permission the code does not define', function () {
    $member = Role::findByName(SystemRole::Member->value);

    Livewire::test('pages::admin.roles.edit', ['role' => $member])
        ->set('permissions', ['everything.forever'])
        ->call('save')
        ->assertHasErrors(['permissions.0']);
});

it('deletes roles made in the panel but keeps system roles', function () {
    $custom = Role::create(['name' => 'gecici', 'label' => 'Geçici', 'guard_name' => 'web']);

    Livewire::test('pages::admin.roles.edit', ['role' => $custom])
        ->call('delete')
        ->assertRedirect(route('admin.roles.index'));
    $this->assertModelMissing($custom);

    $close = Role::findByName(SystemRole::Close->value);
    Livewire::test('pages::admin.roles.edit', ['role' => $close])
        ->call('delete')
        ->assertHasErrors('delete');
    $this->assertModelExists($close);
});
