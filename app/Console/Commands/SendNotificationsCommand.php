<?php

namespace App\Console\Commands;

use App\Enums\NotificationFrequency;
use App\Mail\NotificationDigest;
use App\Models\NotificationItem;
use App\Models\User;
use App\Support\Push\PushNotifier;
use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Facades\Mail;

#[Signature('notifications:send {frequency : instant, daily or weekly}')]
#[Description('E-mail members their waiting notification items, one message each, with a push on their devices')]
class SendNotificationsCommand extends Command
{
    public function handle(PushNotifier $push): int
    {
        $frequency = NotificationFrequency::tryFrom((string) $this->argument('frequency'));

        if ($frequency === null || $frequency === NotificationFrequency::Never) {
            $this->components->error('Frequency must be instant, daily or weekly.');

            return self::FAILURE;
        }

        User::query()
            ->where(fn (Builder $query) => $query
                ->where('notification_frequency', $frequency)
                // Digest-only items of "at once" members go out with the daily digest.
                ->when($frequency === NotificationFrequency::Daily, fn (Builder $query) => $query->orWhere(fn (Builder $query) => $query
                    ->where('notification_frequency', NotificationFrequency::Instant)
                    ->whereHas('notificationItems', fn (Builder $query) => $query->whereNull('sent_at')->where('digest_only', true)))))
            ->whereHas('notificationItems', fn (Builder $query) => $this->waiting($query, $frequency))
            ->each(function (User $user) use ($frequency, $push): void {
                $items = $this->waiting($user->notificationItems(), $frequency)->orderBy('created_at')->orderBy('id')->get();

                if ($user->receivesNotifications()) {
                    $mail = new NotificationDigest($user, $items);
                    Mail::to($user)->send($mail);

                    // One push per e-mail: its subject, and where a tap should lead.
                    $first = $items->first();
                    $push->toUser($user, [
                        'title' => $mail->envelope()->subject ?? 'Defterde yeni bir şey',
                        'body' => $items->count() === 1
                            ? $first?->body
                            : $items->take(3)->map(fn (NotificationItem $item): string => '· '.$item->title)->implode("\n"),
                        'url' => $items->count() === 1 && $first ? $first->url : route('follows.index'),
                        'tag' => 'digest',
                    ]);
                }

                $user->notificationItems()->whereKey($items->modelKeys())->update(['sent_at' => now()]);
            });

        return self::SUCCESS;
    }

    /**
     * Unsent items; the instant run leaves the digest-only ones for the daily digest.
     *
     * @template TQuery of Builder<NotificationItem>|HasMany<NotificationItem, User>
     *
     * @param  TQuery  $query
     * @return TQuery
     */
    private function waiting(Builder|HasMany $query, NotificationFrequency $frequency): Builder|HasMany
    {
        return $query->whereNull('sent_at')->when($frequency === NotificationFrequency::Instant, fn ($query) => $query->where('digest_only', false));
    }
}
