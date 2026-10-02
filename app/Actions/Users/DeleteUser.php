<?php

namespace App\Actions\Users;

use App\Models\User;
use Illuminate\Validation\ValidationException;

/**
 * Deletes a user from the admin panel. Kadir deletes his own account from
 * the account page, never from here, and the last admin cannot be deleted.
 */
class DeleteUser
{
    public function handle(User $actor, User $user): void
    {
        if ($actor->is($user)) {
            throw ValidationException::withMessages(['delete' => 'Kendi hesabını buradan silemezsin.']);
        }

        if ($user->isAdmin() && UpdateUserRoles::adminCount() <= 1) {
            throw ValidationException::withMessages(['delete' => 'Son admin silinemez.']);
        }

        $user->delete();
    }
}
