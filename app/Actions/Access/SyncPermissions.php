<?php

namespace App\Actions\Access;

use App\Enums\Permission;
use App\Enums\SystemRole;
use App\Models\Role;
use Spatie\Permission\Models\Permission as PermissionModel;
use Spatie\Permission\PermissionRegistrar;

/**
 * Brings the permission and role tables in line with the code: creates the
 * permissions of the Permission enum, removes the ones the code no longer
 * knows and makes sure the system roles exist. Safe to run on every deploy.
 */
class SyncPermissions
{
    public function __construct(private PermissionRegistrar $registrar) {}

    public function handle(): void
    {
        $this->registrar->forgetCachedPermissions();

        $names = array_map(fn (Permission $permission): string => $permission->value, Permission::cases());

        foreach ($names as $name) {
            PermissionModel::findOrCreate($name, 'web');
        }

        PermissionModel::query()->whereNotIn('name', $names)->delete();

        foreach (SystemRole::cases() as $systemRole) {
            $role = Role::query()->firstOrNew(['name' => $systemRole->value, 'guard_name' => 'web']);

            if (! $role->exists) {
                $role->label = $systemRole->label();
                $role->save();
                $role->syncPermissions(array_map(
                    fn (Permission $permission): string => $permission->value,
                    $systemRole->defaultPermissions(),
                ));
            }
        }

        $this->registrar->forgetCachedPermissions();
    }
}
