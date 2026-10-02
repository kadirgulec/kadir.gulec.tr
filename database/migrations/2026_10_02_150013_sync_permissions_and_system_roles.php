<?php

use App\Actions\Access\SyncPermissions;
use Illuminate\Database\Migrations\Migration;

/**
 * Creates the permissions and system roles, so every fresh database (tests
 * included) has them. Later changes to the Permission enum reach existing
 * databases through `php artisan permissions:sync` on deploy.
 */
return new class extends Migration
{
    public function up(): void
    {
        app(SyncPermissions::class)->handle();
    }
};
