<?php

use App\Enums\Section;
use App\Http\Controllers\HomeController;
use Illuminate\Support\Facades\Route;

Route::get('/', HomeController::class)->name('home');
Route::view('yazilar', 'site.placeholder', ['section' => Section::Posts])->name('posts.index');
Route::view('izlediklerim', 'site.placeholder', ['section' => Section::Watched])->name('watched.index');
Route::view('hedefler', 'site.placeholder', ['section' => Section::Goals])->name('goals.index');
Route::view('projeler', 'site.placeholder', ['section' => Section::Projects])->name('projects.index');
Route::view('hakkimda', 'site.placeholder', ['section' => Section::About])->name('about');

if (! app()->isProduction()) {
    Route::view('stil', 'site.styleguide', ['section' => Section::Home])->name('styleguide');
}

Route::middleware(['auth', 'verified'])->group(function () {
    Route::view('dashboard', 'dashboard')->name('dashboard');
});

require __DIR__.'/settings.php';
