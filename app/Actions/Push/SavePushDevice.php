<?php

namespace App\Actions\Push;

use App\Listeners\ForgetPushDevice;
use App\Models\PushSubscription;
use App\Models\User;
use Illuminate\Support\Facades\Cookie;
use Illuminate\Validation\Rule;

/**
 * Keeps a browser's push subscription (PushSubscription::toJSON() plus
 * contentEncoding) as a device of $user. A device that changed hands follows
 * whoever saved it last. Used by the notifications settings page and by the
 * installable app when it switches push back on after a sign-in.
 */
class SavePushDevice
{
    /**
     * @param  array<string, mixed>  $subscription
     */
    public function handle(User $user, array $subscription, ?string $userAgent): PushSubscription
    {
        $data = validator($subscription, [
            'endpoint' => ['required', 'string', 'url:https', 'max:2000'],
            'keys.p256dh' => ['required', 'string', 'max:255'],
            'keys.auth' => ['required', 'string', 'max:255'],
            'contentEncoding' => ['nullable', Rule::in(['aes128gcm', 'aesgcm'])],
        ])->validate();

        $hash = PushSubscription::hashOf($data['endpoint']);
        $device = PushSubscription::query()->firstOrNew(['endpoint_hash' => $hash]);
        $device->forceFill([
            'user_id' => $user->id,
            'endpoint' => $data['endpoint'],
            'public_key' => $data['keys']['p256dh'],
            'auth_token' => $data['keys']['auth'],
            'content_encoding' => $data['contentEncoding'] ?? 'aes128gcm',
            'user_agent' => mb_substr((string) $userAgent, 0, 255) ?: null,
        ])->save();

        // Five years: the cookie only tells the logout which device this is.
        Cookie::queue(ForgetPushDevice::COOKIE, $hash, 60 * 24 * 365 * 5);

        return $device;
    }
}
