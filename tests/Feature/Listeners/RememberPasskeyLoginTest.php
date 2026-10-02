<?php

use App\Models\User;
use Laravel\Passkeys\Events\PasskeyVerified;
use Laravel\Passkeys\Passkey;

it('marks the session as verified with a passkey', function () {
    $user = User::factory()->create();
    $request = request();
    $request->setLaravelSession(app('session.store'));

    event(new PasskeyVerified($user, new Passkey));

    expect($request->session()->get(User::PASSKEY_SESSION_KEY))->toBe($user->id)
        ->and($user->hasStrongLogin($request->session()))->toBeTrue();
});
