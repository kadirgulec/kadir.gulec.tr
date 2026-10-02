<?php

use Illuminate\Support\Facades\Route;

/*
 * "Hesabım": profile and security of members (and Kadir), in the notebook design.
 */
Route::middleware(['auth'])->group(function () {
    Route::livewire('hesap', 'pages::settings.profile')->name('profile.edit');
});

Route::middleware(['auth', 'verified'])->group(function () {
    Route::livewire('hesap/guvenlik', 'pages::settings.security')
        ->middleware([
            'password.confirm',
        ])
        ->name('security.edit');
});

Route::get('.well-known/passkey-endpoints', function () {
    return response()->json([
        'enroll' => route('security.edit'),
        'manage' => route('security.edit'),
    ]);
})->name('well-known.passkeys');
