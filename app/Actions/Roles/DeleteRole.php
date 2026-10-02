<?php

namespace App\Actions\Roles;

use App\Models\Role;
use Illuminate\Validation\ValidationException;

/**
 * Deletes a role made in the panel. System roles stay, because the code relies
 * on them (admin for everything, member for every new sign-up).
 */
class DeleteRole
{
    public function handle(Role $role): void
    {
        if ($role->isSystem()) {
            throw ValidationException::withMessages(['delete' => 'Sistem rolleri silinemez.']);
        }

        $role->delete();
    }
}
