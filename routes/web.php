<?php

use App\Enums\Section;
use App\Http\Controllers\AboutController;
use App\Http\Controllers\GoalsController;
use App\Http\Controllers\HomeController;
use App\Http\Controllers\PostsController;
use App\Http\Controllers\ProjectsController;
use App\Http\Controllers\WatchedController;
use Illuminate\Support\Facades\Route;

Route::get('/', HomeController::class)->name('home');
Route::get('yazilar', [PostsController::class, 'index'])->name('posts.index');
Route::get('yazilar/{slug}', [PostsController::class, 'show'])->name('posts.show');
Route::get('izlediklerim', [WatchedController::class, 'index'])->name('watched.index');
Route::get('izlediklerim/{type}/{slug}', [WatchedController::class, 'show'])
    ->whereIn('type', ['film', 'dizi'])
    ->name('watched.show');
Route::get('hedefler', [GoalsController::class, 'index'])->name('goals.index');
Route::get('hedefler/zincir/{slug}', [GoalsController::class, 'chain'])->name('goals.chain');
Route::get('hedefler/{slug}', [GoalsController::class, 'show'])->name('goals.show');
Route::get('projeler', [ProjectsController::class, 'index'])->name('projects.index');
Route::get('projeler/{slug}', [ProjectsController::class, 'show'])->name('projects.show');
Route::get('hakkimda', AboutController::class)->name('about');

if (! app()->isProduction()) {
    Route::view('stil', 'site.styleguide', ['section' => Section::Home])->name('styleguide');
}

Route::middleware(['auth', 'verified', 'admin'])->prefix('admin')->name('admin.')->group(function () {
    Route::livewire('/', 'pages::admin.dashboard')->name('dashboard');

    if (! app()->isProduction()) {
        Route::livewire('stil', 'pages::admin.styleguide')->name('styleguide');
    }
});

require __DIR__.'/settings.php';
