<?php

namespace App\Actions\Users;

use App\Models\User;
use Illuminate\Validation\ValidationException;

/**
 * Blocks or unblocks a user. A blocked user can still sign in, but cannot
 * comment and gets no e-mail; their comments are hidden until unblocked.
 */
class BlockUser
{
    public function block(User $actor, User $user): void
    {
        if ($actor->is($user)) {
            throw ValidationException::withMessages(['block' => 'Kendini engelleyemezsin.']);
        }

        if ($user->isAdmin()) {
            throw ValidationException::withMessages(['block' => 'Bir admin engellenemez. Önce admin rolünü kaldır.']);
        }

        $user->forceFill(['blocked_at' => now()])->save();
    }

    public function unblock(User $user): void
    {
        $user->forceFill(['blocked_at' => null])->save();
    }
}
