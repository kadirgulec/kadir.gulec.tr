<?php

namespace App\Console\Commands;

use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;
use Minishlink\WebPush\VAPID;

#[Signature('push:vapid')]
#[Description('Print a new VAPID key pair for push notifications, to paste into .env')]
class CreateVapidKeysCommand extends Command
{
    public function handle(): int
    {
        $keys = VAPID::createVapidKeys();

        $this->line('VAPID_PUBLIC_KEY='.$keys['publicKey']);
        $this->line('VAPID_PRIVATE_KEY='.$keys['privateKey']);
        $this->newLine();
        $this->components->warn('Create them once. New keys silently end every existing push subscription.');

        return self::SUCCESS;
    }
}
