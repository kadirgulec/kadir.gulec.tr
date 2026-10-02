<?php

namespace App\Console\Commands;

use App\Enums\NotificationFrequency;
use App\Mail\NotificationDigest;
use App\Models\User;
use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Mail;

#[Signature('notifications:send {frequency : instant, daily or weekly}')]
#[Description('E-mail members their waiting notification items, one message each')]
class SendNotificationsCommand extends Command
{
    public function handle(): int
    {
        $frequency = NotificationFrequency::tryFrom((string) $this->argument('frequency'));

        if ($frequency === null || $frequency === NotificationFrequency::Never) {
            $this->components->error('Frequency must be instant, daily or weekly.');

            return self::FAILURE;
        }

        User::query()
            ->where('notification_frequency', $frequency)
            ->whereHas('notificationItems', fn ($query) => $query->whereNull('sent_at'))
            ->each(function (User $user): void {
                $items = $user->notificationItems()->whereNull('sent_at')->orderBy('created_at')->orderBy('id')->get();

                if ($user->receivesNotifications()) {
                    Mail::to($user)->send(new NotificationDigest($user, $items));
                }

                $user->notificationItems()->whereKey($items->modelKeys())->update(['sent_at' => now()]);
            });

        return self::SUCCESS;
    }
}
