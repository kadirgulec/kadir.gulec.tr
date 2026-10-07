<?php

namespace App\Listeners;

use App\Models\PushSubscription;
use Illuminate\Auth\Events\Logout;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cookie;

/**
 * Signing out stops push on this device: the server forgets the device, and
 * the next page tells the browser to unsubscribe as well (resources/js/pwa.js).
 * A session that simply runs out is no sign-out: push keeps coming then, and
 * tapping a notification leads to the login.
 */
class ForgetPushDevice
{
    public const COOKIE = 'push_device';

    /** Left for the next page: drop this browser's push subscription too. */
    public const NOTICE_COOKIE = 'push_forget';

    public function __construct(private Request $request) {}

    public function handle(Logout $event): void
    {
        $hash = $this->request->cookie(self::COOKIE);

        if (is_string($hash) && $hash !== '') {
            PushSubscription::query()->where('endpoint_hash', $hash)->delete();
        }

        Cookie::queue(Cookie::forget(self::COOKIE));
        Cookie::queue(self::NOTICE_COOKIE, '1', 60 * 24);
    }

    /**
     * Whether a logout left a notice for this page; reading it clears it.
     */
    public static function takeNotice(Request $request): bool
    {
        if (! $request->hasCookie(self::NOTICE_COOKIE)) {
            return false;
        }

        Cookie::queue(Cookie::forget(self::NOTICE_COOKIE));

        return true;
    }
}
