<?php

namespace App\Jobs;

use App\Support\Backups\BackupManager;
use Illuminate\Contracts\Queue\ShouldBeUnique;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;

/**
 * Makes a backup in the background: with all uploads the zip can take a
 * while, longer than a browser request should wait.
 */
class CreateBackup implements ShouldBeUnique, ShouldQueue
{
    use Queueable;

    public int $timeout = 900;

    public int $tries = 1;

    public function handle(BackupManager $backups): void
    {
        $backups->create();
    }
}
