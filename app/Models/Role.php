<?php

namespace App\Models;

use App\Enums\SystemRole;
use Carbon\CarbonImmutable;
use Spatie\Permission\Models\Role as SpatieRole;

/**
 * A role as shown in the admin panel. `name` is the stable machine name the
 * code checks; `label` is what Kadir sees and may rename.
 *
 * @property int $id
 * @property string $name
 * @property string|null $label
 * @property string $guard_name
 * @property CarbonImmutable|null $created_at
 * @property CarbonImmutable|null $updated_at
 */
class Role extends SpatieRole
{
    public function systemRole(): ?SystemRole
    {
        return SystemRole::tryFrom($this->name);
    }

    /**
     * System roles may be renamed, but never deleted.
     */
    public function isSystem(): bool
    {
        return $this->systemRole() !== null;
    }

    public function isAdmin(): bool
    {
        return $this->systemRole() === SystemRole::Admin;
    }

    public function displayName(): string
    {
        return $this->label ?? $this->systemRole()?->label() ?? $this->name;
    }
}
