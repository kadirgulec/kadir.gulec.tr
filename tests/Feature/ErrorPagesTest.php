<?php

use App\Models\Goal;
use App\Models\Post;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Route;

beforeEach(function () {
    config(['app.debug' => false]);
});

it('shows the torn notebook page for a missing address, kept out of search engines', function () {
    $this->get('/boyle-bir-sayfa-yok')
        ->assertNotFound()
        ->assertSeeText('Buraya bir şey yazacaktım…')
        ->assertSeeText('başka sayfalara bak:')
        ->assertSee('<meta name="robots" content="noindex, nofollow" />', escape: false)
        ->assertDontSeeText('Bunu mu aradın?');
});

it('suggests the published page with the closest address', function () {
    $post = Post::factory()->create(['title' => 'Neden blog yazısı?']);

    $this->get('/yazilar/neden-blog')
        ->assertNotFound()
        ->assertSeeText('Bunu mu aradın?')
        ->assertSee('href="'.url($post->publicPath()).'"', escape: false)
        ->assertSeeText('Neden blog yazısı?');
});

it('never suggests drafts or goals that are not public', function () {
    Post::factory()->draft()->create(['title' => 'Gizli taslak yazı']);
    Goal::factory()->chain('xx')->censored()->create(['title' => 'Ekransız sabahlar']);

    $this->get('/yazilar/gizli-taslak')->assertNotFound()->assertDontSeeText('Gizli taslak yazı');
    $this->get('/hedefler/zincir/ekransiz-sabahlar')->assertNotFound()->assertDontSeeText('Ekransız sabahlar');
});

it('has its own notebook page for every error the site can answer with', function (int $status, string $heading) {
    Route::get('/_test/hata', fn () => abort($status))->middleware('web');

    $this->get('/_test/hata')->assertStatus($status)->assertSeeText($heading);
})->with([
    [403, 'Bu sayfa kilitli bir çekmecede.'],
    [419, 'Kalemin mürekkebi kurumuş.'],
    [429, 'Biraz yavaş, kalem yetişemiyor.'],
    [503, 'Defteri yeniden ciltliyorum.'],
]);

it('shows the 500 page without the error message', function () {
    Route::get('/_test/patla', fn () => throw new RuntimeException('Gizli ayrıntı'));

    $this->get('/_test/patla')
        ->assertStatus(500)
        ->assertSeeText('Mürekkep döküldü.')
        ->assertDontSeeText('Gizli ayrıntı');
});

it('renders the 500 and 503 pages without touching the database', function (string $view) {
    $queries = 0;
    DB::listen(function () use (&$queries) {
        $queries++;
    });

    $html = view($view)->render();

    expect($queries)->toBe(0)->and($html)->toContain('torn-edge');
})->with(['errors.500', 'errors.503']);
