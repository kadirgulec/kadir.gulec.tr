<?php

use App\Enums\Section;
use App\Http\Controllers\GoalsController;
use App\Http\Controllers\HomeController;
use App\Http\Controllers\PostsController;
use App\Http\Controllers\WatchedController;
use Illuminate\Support\Facades\Route;

Route::get('/', HomeController::class)->name('home');
Route::get('yazilar', [PostsController::class, 'index'])->name('posts.index');
Route::get('yazilar/{slug}', [PostsController::class, 'show'])->name('posts.show');
Route::get('izlediklerim', [WatchedController::class, 'index'])->name('watched.index');
Route::get('izlediklerim/{type}/{slug}', [WatchedController::class, 'show'])
    ->whereIn('type', ['film', 'dizi'])
    ->name('watched.show');
Route::get('hedefler', GoalsController::class)->name('goals.index');
Route::view('projeler', 'site.placeholder', ['section' => Section::Projects])->name('projects.index');
Route::view('hakkimda', 'site.placeholder', ['section' => Section::About])->name('about');

if (! app()->isProduction()) {
    Route::view('stil', 'site.styleguide', ['section' => Section::Home])->name('styleguide');
}

Route::middleware(['auth', 'verified'])->group(function () {
    Route::view('dashboard', 'dashboard')->name('dashboard');
});

require __DIR__.'/settings.php';
