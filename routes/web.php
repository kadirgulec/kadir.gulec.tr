<?php

use App\Enums\Permission;
use App\Enums\Section;
use App\Http\Controllers\AboutController;
use App\Http\Controllers\Admin\BackupDownloadController;
use App\Http\Controllers\Admin\MarkdownPreviewController;
use App\Http\Controllers\FeedController;
use App\Http\Controllers\GoalsController;
use App\Http\Controllers\HomeController;
use App\Http\Controllers\OgImageController;
use App\Http\Controllers\PostsController;
use App\Http\Controllers\ProjectsController;
use App\Http\Controllers\SitemapController;
use App\Http\Controllers\WatchedController;
use Illuminate\Support\Facades\Route;

Route::get('/', HomeController::class)->name('home');
Route::get('yazilar', [PostsController::class, 'index'])->name('posts.index');
Route::get('yazilar/rss', [FeedController::class, 'posts'])->name('posts.feed');
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
Route::view('gizlilik', 'site.legal.privacy')->name('privacy');
Route::get('sitemap.xml', [SitemapController::class, 'sitemap'])->name('sitemap');
Route::get('robots.txt', [SitemapController::class, 'robots'])->name('robots');
Route::get('og/{kind}/{key}.png', OgImageController::class)->where('key', '[a-z0-9-]+')->name('og');
Route::view('kunye', 'site.legal.imprint')->name('imprint');

if (! app()->isProduction()) {
    Route::view('stil', 'site.styleguide', ['section' => Section::Home])->name('styleguide');
}

Route::middleware(['auth', 'verified', 'admin'])->prefix('admin')->name('admin.')->group(function () {
    Route::livewire('/', 'pages::admin.dashboard')->name('dashboard');
    Route::post('onizleme', MarkdownPreviewController::class)->name('markdown.preview');

    Route::middleware('can:'.Permission::ManagePosts->value)->group(function () {
        Route::livewire('yazilar', 'pages::admin.posts.index')->name('posts.index');
        Route::livewire('yazilar/yeni', 'pages::admin.posts.edit')->name('posts.create');
        Route::livewire('yazilar/etiketler', 'pages::admin.tags.index')->name('tags.index');
        Route::livewire('yazilar/{post}', 'pages::admin.posts.edit')->name('posts.edit');
    });

    Route::middleware('can:'.Permission::ManageGoals->value)->group(function () {
        Route::livewire('hedefler', 'pages::admin.goals.index')->name('goals.index');
        Route::livewire('hedefler/yeni/{kind}', 'pages::admin.goals.edit')->whereIn('kind', ['zincir', 'yillik', 'uzun-vade'])->name('goals.create');
        Route::livewire('hedefler/{goal}', 'pages::admin.goals.edit')->name('goals.edit');
    });

    Route::middleware('can:'.Permission::ManageWatched->value)->group(function () {
        Route::livewire('izlediklerim', 'pages::admin.watched.index')->name('watched.index');
        Route::livewire('izlediklerim/ekle', 'pages::admin.watched.create')->name('watched.create');
        Route::livewire('izlediklerim/{watchable}', 'pages::admin.watched.edit')->name('watched.edit');
    });

    Route::middleware('can:'.Permission::ManageProjects->value)->group(function () {
        Route::livewire('projeler', 'pages::admin.projects.index')->name('projects.index');
        Route::livewire('projeler/yeni', 'pages::admin.projects.edit')->name('projects.create');
        Route::livewire('projeler/alet-cantasi', 'pages::admin.projects.toolbox')->name('projects.toolbox');
        Route::livewire('projeler/{project}', 'pages::admin.projects.edit')->name('projects.edit');
    });

    Route::middleware('can:'.Permission::ManageBackups->value)->group(function () {
        Route::livewire('yedekler', 'pages::admin.backups.index')->name('backups.index');
        Route::get('yedekler/{name}', BackupDownloadController::class)->middleware('signed')->where('name', 'yedek-[0-9a-z-]+\\.zip')->name('backups.download');
    });

    Route::livewire('mesajlar', 'pages::admin.messages.index')->middleware('can:'.Permission::ReadMessages->value)->name('messages.index');

    Route::livewire('yorumlar', 'pages::admin.comments.index')->middleware('can:'.Permission::ModerateComments->value)->name('comments.index');

    Route::middleware('can:'.Permission::ManageUsers->value)->group(function () {
        Route::livewire('kullanicilar', 'pages::admin.users.index')->name('users.index');
        Route::livewire('kullanicilar/{user}', 'pages::admin.users.show')->name('users.show');
    });

    Route::middleware('can:'.Permission::ManageRoles->value)->group(function () {
        Route::livewire('roller', 'pages::admin.roles.index')->name('roles.index');
        Route::livewire('roller/{role}', 'pages::admin.roles.edit')->name('roles.edit');
    });

    if (! app()->isProduction()) {
        Route::livewire('stil', 'pages::admin.styleguide')->name('styleguide');
    }
});

require __DIR__.'/settings.php';
