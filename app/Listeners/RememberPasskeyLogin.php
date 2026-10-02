<?php

namespace App\Listeners;

use App\Models\User;
use Illuminate\Http\Request;
use Laravel\Passkeys\Events\PasskeyVerified;

/**
 * Marks the session as passkey-verified, which the admin panel accepts as a
 * strong login. The mark survives the session id regeneration after login.
 */
class RememberPasskeyLogin
{
    public function __construct(private Request $request) {}

    public function handle(PasskeyVerified $event): void
    {
        if ($this->request->hasSession()) {
            $this->request->session()->put(User::PASSKEY_SESSION_KEY, $event->user->getAuthIdentifier());
        }
    }
}
