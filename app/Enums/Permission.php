<?php

namespace App\Enums;

/**
 * Every permission the code checks. Permissions are defined here, never in the
 * admin panel: a permission without a check in the code would protect nothing.
 * The panel only creates roles and assigns these permissions to them.
 */
enum Permission: string
{
    case AccessAdmin = 'admin.access';
    case ManagePosts = 'posts.manage';
    case ManageWatched = 'watched.manage';
    case ManageGoals = 'goals.manage';
    case ManageProjects = 'projects.manage';
    case ManageUsers = 'users.manage';
    case ManageRoles = 'roles.manage';
    case ManageBackups = 'backups.manage';
    case CreateComments = 'comments.create';
    case ModerateComments = 'comments.moderate';
    case Follow = 'follows.create';
    case ViewCensoredGoals = 'goals.view-censored';

    public function label(): string
    {
        return match ($this) {
            self::AccessAdmin => 'Admin paneline girebilir',
            self::ManagePosts => 'Yazıları yönetir',
            self::ManageWatched => 'İzlediklerimi yönetir',
            self::ManageGoals => 'Hedefleri yönetir',
            self::ManageProjects => 'Projeleri yönetir',
            self::ManageUsers => 'Kullanıcıları yönetir',
            self::ManageRoles => 'Rolleri yönetir',
            self::ManageBackups => 'Yedek alır ve geri yükler',
            self::CreateComments => 'Yorum yazabilir',
            self::ModerateComments => 'Yorumları onaylar ve siler',
            self::Follow => 'İçerik takip edebilir',
            self::ViewCensoredGoals => 'Sansürlü hedeflerin metnini görür',
        };
    }

    /**
     * The heading the permission is listed under on the roles screen.
     */
    public function group(): string
    {
        return match ($this) {
            self::AccessAdmin, self::ManageBackups => 'Admin',
            self::ManagePosts, self::ManageWatched, self::ManageGoals, self::ManageProjects => 'İçerik',
            self::ManageUsers, self::ManageRoles => 'Üyeler',
            self::CreateComments, self::ModerateComments => 'Yorumlar',
            self::Follow, self::ViewCensoredGoals => 'Takip ve hedefler',
        };
    }

    /**
     * @return array<string, list<self>>
     */
    public static function grouped(): array
    {
        $groups = [];

        foreach (self::cases() as $permission) {
            $groups[$permission->group()][] = $permission;
        }

        return $groups;
    }
}
