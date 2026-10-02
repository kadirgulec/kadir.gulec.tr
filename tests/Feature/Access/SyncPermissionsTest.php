<?php

use App\Actions\Access\SyncPermissions;
use App\Enums\Permission;
use App\Enums\SystemRole;
use App\Models\Role;
use App\Models\User;
use Spatie\Permission\Models\Permission as PermissionModel;

it('creates every permission of the enum and the system roles', function () {
    expect(PermissionModel::pluck('name')->sort()->values()->all())
        ->toBe(collect(Permission::cases())->pluck('value')->sort()->values()->all())
        ->and(Role::pluck('name')->sort()->values()->all())
        ->toBe(['admin', 'close', 'member']);
});

it('removes permissions the code no longer defines', function () {
    PermissionModel::create(['name' => 'posts.publish-everywhere', 'guard_name' => 'web']);

    app(SyncPermissions::class)->handle();

    expect(PermissionModel::where('name', 'posts.publish-everywhere')->exists())->toBeFalse();
});

it('keeps the renamed label and the edited permissions of a system role', function () {
    $member = Role::findByName(SystemRole::Member->value);
    $member->update(['label' => 'Okur']);
    $member->syncPermissions([Permission::Follow->value]);

    app(SyncPermissions::class)->handle();

    $member->refresh();
    expect($member->label)->toBe('Okur')
        ->and($member->permissions->pluck('name')->all())->toBe([Permission::Follow->value]);
});

test('roles get their default permissions and the admin may do everything', function () {
    $admin = User::factory()->admin()->create();
    $member = User::factory()->member()->create();
    $close = User::factory()->close()->create();

    foreach (Permission::cases() as $permission) {
        expect($admin->can($permission->value))->toBeTrue();
    }

    expect($member->can(Permission::CreateComments->value))->toBeTrue()
        ->and($member->can(Permission::Follow->value))->toBeTrue()
        ->and($member->can(Permission::ViewCensoredGoals->value))->toBeFalse()
        ->and($member->can(Permission::AccessAdmin->value))->toBeFalse()
        ->and($close->can(Permission::ViewCensoredGoals->value))->toBeTrue()
        ->and($close->can(Permission::ModerateComments->value))->toBeFalse();
});
