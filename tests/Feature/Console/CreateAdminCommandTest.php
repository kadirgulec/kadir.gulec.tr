<?php

use App\Models\User;

it('creates a verified admin', function () {
    $this->artisan('user:create-admin', [
        '--name' => 'Kadir',
        '--email' => 'kadir@example.com',
        '--password' => 'a-long-password',
    ])->assertSuccessful();

    $user = User::where('email', 'kadir@example.com')->sole();
    expect($user->isAdmin())->toBeTrue()
        ->and($user->hasVerifiedEmail())->toBeTrue();
});

it('promotes an existing user without touching the password', function () {
    $user = User::factory()->member()->create();
    $passwordHash = $user->password;

    $this->artisan('user:create-admin', ['--email' => $user->email])->assertSuccessful();

    $user->refresh();
    expect($user->isAdmin())->toBeTrue()
        ->and($user->password)->toBe($passwordHash);
});

it('rejects an invalid e-mail address', function () {
    $this->artisan('user:create-admin', [
        '--name' => 'Kadir',
        '--email' => 'not-an-email',
        '--password' => 'a-long-password',
    ])->assertFailed();

    expect(User::count())->toBe(0);
});
