<?php

namespace App\Enums;

/**
 * The roles the code relies on. Their machine names never change; the admin
 * panel may rename their labels, but cannot delete them. Roles created in the
 * panel are ordinary rows without a case here.
 */
enum SystemRole: string
{
    /** Kadir. Allowed everything through Gate::before, so it needs no permissions. */
    case Admin = 'admin';

    /** Everyone who registers. */
    case Member = 'member';

    /** Family and friends Kadir picks by hand. */
    case Close = 'close';

    public function label(): string
    {
        return match ($this) {
            self::Admin => 'Admin',
            self::Member => 'Üye',
            self::Close => 'Yakın',
        };
    }

    /**
     * The permissions the role starts with. Admin has none on purpose.
     *
     * @return list<Permission>
     */
    public function defaultPermissions(): array
    {
        return match ($this) {
            self::Admin => [],
            self::Member => [Permission::CreateComments, Permission::Follow],
            self::Close => [Permission::CreateComments, Permission::Follow, Permission::ViewCensoredGoals],
        };
    }
}
