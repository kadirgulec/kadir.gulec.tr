<?php

namespace App\Listeners;

use App\Models\PushSubscription;
use Illuminate\Auth\Events\Logout;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cookie;

/**
 * Push goes to devices someone is signed in on: signing out forgets this
 * device. The page signed out on also unsubscribes the browser itself
 * (resources/js/pwa.js), so the push service lets go of it as well.
 */
class ForgetPushDevice
{
    public const COOKIE = 'push_device';

    public function __construct(private Request $request) {}

    public function handle(Logout $event): void
    {
        $hash = $this->request->cookie(self::COOKIE);

        if (is_string($hash) && $hash !== '') {
            PushSubscription::query()->where('endpoint_hash', $hash)->delete();
        }

        Cookie::queue(Cookie::forget(self::COOKIE));
    }
}
