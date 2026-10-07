<?php

use App\Http\Controllers\Account\DataExportController;
use App\Http\Controllers\Account\PushDeviceController;
use App\Http\Controllers\Account\UnsubscribeController;
use Illuminate\Support\Facades\Route;

/*
 * "Hesabım": profile and security of members (and Kadir), in the notebook design.
 */
Route::middleware(['auth'])->group(function () {
    Route::livewire('hesap', 'pages::settings.profile')->name('profile.edit');
    Route::get('hesap/verilerim', DataExportController::class)->middleware('throttle:6,1')->name('account.export');
});

Route::middleware(['auth', 'verified'])->group(function () {
    Route::livewire('hesap/takip', 'pages::settings.follows')->name('follows.index');
    Route::livewire('hesap/bildirimler', 'pages::settings.notifications')->name('notifications.edit');
    Route::post('hesap/bildirimler/cihaz', PushDeviceController::class)->middleware('throttle:30,1')->name('push-devices.store');

    Route::livewire('hesap/guvenlik', 'pages::settings.security')
        ->middleware([
            'password.confirm',
        ])
        ->name('security.edit');
});

// Links in notification e-mails: signed, no sign-in needed.
Route::middleware('signed')->group(function () {
    Route::match(['get', 'post'], 'bildirimler/kapat/{user}', [UnsubscribeController::class, 'all'])->name('notifications.unsubscribe');
    Route::match(['get', 'post'], 'takip/birak/{follow}', [UnsubscribeController::class, 'follow'])->name('follows.unsubscribe');
});

Route::get('.well-known/passkey-endpoints', function () {
    return response()->json([
        'enroll' => route('security.edit'),
        'manage' => route('security.edit'),
    ]);
})->name('well-known.passkeys');
