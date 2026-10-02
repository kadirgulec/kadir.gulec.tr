<?php

namespace App\Actions\Roles;

use App\Enums\Permission;
use App\Models\Role;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

/**
 * Creates or updates a role: its label and its permissions. The machine name
 * of a new role comes from the label and never changes afterwards. The admin
 * role is allowed everything anyway, so its permissions cannot be edited.
 */
class SaveRole
{
    /**
     * @param  array{label: string, permissions: list<string>}  $input
     */
    public function handle(?Role $role, array $input): Role
    {
        $role ??= new Role(['guard_name' => 'web']);

        $validated = Validator::make($input, [
            'label' => ['required', 'string', 'max:60'],
            'permissions' => ['array'],
            'permissions.*' => [Rule::enum(Permission::class)],
        ])->validate();

        if ($this->labelIsTaken($role, $validated['label'])) {
            throw ValidationException::withMessages(['label' => 'Bu adda bir rol zaten var.']);
        }

        if (! $role->exists) {
            $name = Str::slug($validated['label']);

            if ($name === '' || Role::query()->where('name', $name)->exists()) {
                throw ValidationException::withMessages(['label' => 'Bu adda bir rol zaten var.']);
            }

            $role->name = $name;
        }

        $role->label = $validated['label'];
        $role->save();

        if (! $role->isAdmin()) {
            $role->syncPermissions($validated['permissions'] ?? []);
        }

        return $role;
    }

    /**
     * Two roles must never look the same in the panel.
     */
    private function labelIsTaken(Role $role, string $label): bool
    {
        $wanted = Str::lower($label);

        return Role::query()
            ->when($role->exists, fn ($query) => $query->whereKeyNot($role->getKey()))
            ->get()
            ->contains(fn (Role $other): bool => Str::lower($other->displayName()) === $wanted);
    }
}
