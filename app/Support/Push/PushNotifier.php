<?php

namespace App\Support\Push;

use App\Enums\Permission;
use App\Models\PushSubscription;
use App\Models\User;
use Illuminate\Support\Collection;
use Minishlink\WebPush\Subscription;
use Minishlink\WebPush\WebPush;
use Throwable;

/**
 * Sends the push notification that goes with an e-mail, to the devices a
 * member switched push on for (signed in there). Without VAPID keys it does
 * nothing, and a failing push never stops the e-mail it belongs to. Devices
 * the push service no longer knows are forgotten.
 */
class PushNotifier
{
    /** How long a push service keeps trying to deliver (seconds). */
    private const TIME_TO_LIVE = 86400;

    public static function publicKey(): ?string
    {
        return config('services.webpush.public_key') ?: null;
    }

    public function enabled(): bool
    {
        return self::publicKey() !== null && filled(config('services.webpush.private_key'));
    }

    /**
     * @param  array{title: string, body?: ?string, url: string, tag?: string}  $message
     */
    public function toUser(User $user, array $message): void
    {
        $this->send($user->pushSubscriptions()->get(), $message);
    }

    /**
     * Kadir's own e-mails (chain reminders, contact messages) go to whoever
     * may act on them: every member with $permission (the admin has all).
     *
     * @param  array{title: string, body?: ?string, url: string, tag?: string}  $message
     */
    public function toPermitted(Permission $permission, array $message): void
    {
        $users = User::query()->whereHas('pushSubscriptions')->with('pushSubscriptions')->get()
            ->filter(fn (User $user): bool => $user->can($permission->value));

        $this->send($users->flatMap(fn (User $user) => $user->pushSubscriptions), $message);
    }

    /**
     * @param  Collection<int, PushSubscription>  $subscriptions
     * @param  array{title: string, body?: ?string, url: string, tag?: string}  $message
     */
    private function send(Collection $subscriptions, array $message): void
    {
        if ($subscriptions->isEmpty() || ! $this->enabled()) {
            return;
        }

        try {
            $this->deliver($subscriptions, $message);
        } catch (Throwable $exception) {
            report($exception);
        }
    }

    /**
     * Hands the message to the push services and tidies up after their answers.
     *
     * @param  Collection<int, PushSubscription>  $subscriptions
     * @param  array{title: string, body?: ?string, url: string, tag?: string}  $message
     */
    protected function deliver(Collection $subscriptions, array $message): void
    {
        $webPush = new WebPush(['VAPID' => [
            'subject' => config('services.webpush.subject') ?: 'mailto:'.config('legal.email'),
            'publicKey' => self::publicKey(),
            'privateKey' => config('services.webpush.private_key'),
        ]], ['TTL' => self::TIME_TO_LIVE]);

        $payload = (string) json_encode([
            'title' => $message['title'],
            'body' => $message['body'] ?? null,
            'url' => $message['url'],
            'tag' => $message['tag'] ?? null,
        ], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);

        foreach ($subscriptions as $subscription) {
            $webPush->queueNotification(
                new Subscription($subscription->endpoint, $subscription->public_key, $subscription->auth_token, $subscription->content_encoding),
                $payload,
            );
        }

        foreach ($webPush->flush() as $report) {
            $device = PushSubscription::query()->where('endpoint_hash', PushSubscription::hashOf($report->getEndpoint()));

            if ($report->isSuccess()) {
                $device->update(['last_used_at' => now()]);
            } elseif ($report->isSubscriptionExpired()) {
                $device->delete();
            }
        }
    }
}
