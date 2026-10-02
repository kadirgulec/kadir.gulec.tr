<?php

namespace App\Console\Commands;

use App\Actions\Access\SyncPermissions;
use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;

#[Signature('permissions:sync')]
#[Description('Create the permissions and system roles the code defines (run on every deploy)')]
class SyncPermissionsCommand extends Command
{
    public function handle(SyncPermissions $syncPermissions): int
    {
        $syncPermissions->handle();

        $this->components->info('Permissions and system roles are in sync.');

        return self::SUCCESS;
    }
}
