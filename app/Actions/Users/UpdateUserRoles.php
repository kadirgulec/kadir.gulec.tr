<?php

namespace App\Actions\Users;

use App\Enums\SystemRole;
use App\Models\Role;
use App\Models\User;
use Illuminate\Validation\ValidationException;

/**
 * Gives a user exactly the given roles. Nobody can take the admin role away
 * from themselves, and the last admin always stays an admin.
 */
class UpdateUserRoles
{
    /**
     * @param  list<int>  $roleIds
     */
    public function handle(User $actor, User $user, array $roleIds): void
    {
        $roles = Role::query()->whereKey($roleIds)->get();
        $keepsAdmin = $roles->contains(fn (Role $role): bool => $role->isAdmin());

        if ($user->isAdmin() && ! $keepsAdmin) {
            if ($actor->is($user)) {
                throw ValidationException::withMessages(['roles' => 'Kendi admin rolünü kaldıramazsın.']);
            }

            if (self::adminCount() <= 1) {
                throw ValidationException::withMessages(['roles' => 'Son admin bu rolü kaybedemez.']);
            }
        }

        $user->syncRoles($roles);
    }

    public static function adminCount(): int
    {
        return User::role(SystemRole::Admin->value)->count();
    }
}
