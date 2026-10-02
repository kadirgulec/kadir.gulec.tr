<?php

use App\Enums\GoalMeasure;
use App\Models\Goal;
use App\Models\Project;
use App\Models\User;
use Carbon\CarbonImmutable;

/**
 * A board with one goal of each kind and visibility, the way the prototype had it.
 *
 * @return array<string, Goal>
 */
function goalBoard(): array
{
    $product = Goal::factory()->longTerm()->create(['title' => 'Kendi ürünümü çıkarmak', 'slug' => 'kendi-urunum']);
    $secretLongTerm = Goal::factory()->longTerm()->censored()->create(['title' => 'Çok kişisel bir hedef', 'why' => 'Bunu sadece ben biliyorum.']);
    $secretLongTerm->updates()->create(['date' => '2026-05-01', 'body' => 'Gizli bir not.']);
    $hiddenLongTerm = Goal::factory()->longTerm()->hidden()->create(['title' => 'Kimsenin bilmediği pano']);

    $code = Goal::factory()->chain(str_repeat('x', 23))->create(['title' => 'Her gün 30 dk kod', 'slug' => 'her-gun-kod', 'parent_id' => $product->id]);
    $secretChain = Goal::factory()->chain(str_repeat('x', 41))->censored()->create(['title' => 'Ekransız sabahlar']);
    $hiddenChain = Goal::factory()->chain('xxx')->hidden()->create(['title' => 'Tamamen gizli bir alışkanlık']);

    $comon = Goal::factory()->yearly(GoalMeasure::Milestones)->create(['title' => "CoMon'u herkese açık yayınla", 'parent_id' => $product->id]);
    $comon->milestones()->createMany([['title' => 'Kapalı beta', 'done_at' => now()], ['title' => 'Herkese açık yayın']]);
    $secretYearly = Goal::factory()->yearly()->censored()->create(['title' => 'Kimseye söylemediğim bir hedef', 'show_progress_notes' => true]);
    $secretYearly->progressEntries()->create(['date' => '2026-02-01', 'note' => 'Ele veren bir not']);
    $underSecretParent = Goal::factory()->yearly(GoalMeasure::Binary)->create(['title' => 'Açık ama üstü sansürlü', 'parent_id' => $secretLongTerm->id]);
    $underHiddenParent = Goal::factory()->yearly(GoalMeasure::Binary)->create(['title' => 'Açık ama üstü gizli', 'parent_id' => $hiddenLongTerm->id]);

    return compact('product', 'secretLongTerm', 'hiddenLongTerm', 'code', 'secretChain', 'hiddenChain', 'comon', 'secretYearly', 'underSecretParent', 'underHiddenParent');
}

it('shows the three floors from daily chains to the long-term board', function () {
    goalBoard();
    Goal::factory()->yearly(GoalMeasure::Binary, 2025)->create(['title' => 'Yarı maraton koş']);

    $this->get(route('goals.index'))
        ->assertSeeTextInOrder(['Zincirler', 'Her gün 30 dk kod', 'hedefleri', "CoMon'u herkese açık yayınla", 'Geçmiş yıllar', 'Yarı maraton koş', 'Uzun vade', 'Kendi ürünümü çıkarmak']);
});

describe('censored goals', function () {
    it('never sends their words, slugs or notes to visitors and members', function (?string $role) {
        $board = goalBoard();

        if ($role !== null) {
            $this->actingAs(User::factory()->{$role}()->create());
        }

        $this->get(route('goals.index'))
            ->assertDontSee('Ekransız sabahlar')
            ->assertDontSee($board['secretChain']->slug)
            ->assertDontSee('Kimseye söylemediğim bir hedef')
            ->assertDontSee('Ele veren bir not')
            ->assertDontSee('Çok kişisel bir hedef')
            ->assertDontSee('sadece ben biliyorum')
            ->assertSeeText('🔥 41')
            ->assertSee('id="hedef-k-'.$board['secretChain']->id.'"', false);
    })->with(['visitor' => [null], 'member' => ['member']]);

    it('shows their words to close friends and the admin', function (string $role) {
        $board = goalBoard();

        $this->actingAs(User::factory()->{$role}()->create())
            ->get(route('goals.index'))
            ->assertSeeText('Ekransız sabahlar')
            ->assertSeeText('Kimseye söylemediğim bir hedef')
            ->assertSeeText('Ele veren bir not')
            ->assertSeeText('Çok kişisel bir hedef')
            ->assertSee('id="hedef-'.$board['secretChain']->slug.'"', false);
    })->with(['close', 'admin']);

    it('blacks out the chip of a censored parent and drops the chip of a hidden one', function () {
        $board = goalBoard();

        $this->get(route('goals.index'))
            ->assertSee('href="#hedef-kendi-urunum"', false)
            ->assertSee('id="hedef-kendi-urunum"', false)
            ->assertSee('href="#hedef-k-'.$board['secretLongTerm']->id.'"', false)
            ->assertSeeText('Açık ama üstü gizli')
            ->assertDontSee($board['hiddenLongTerm']->slug);
    });
});

it('leaves hidden goals out for everyone, the admin included', function (?string $role) {
    goalBoard();

    if ($role !== null) {
        $this->actingAs(User::factory()->{$role}()->create());
    }

    $this->get(route('goals.index'))
        ->assertDontSee('Tamamen gizli bir alışkanlık')
        ->assertDontSee('Kimsenin bilmediği pano');
})->with(['visitor' => [null], 'close' => ['close'], 'admin' => ['admin']]);

it('tells whether a numeric goal is on track', function () {
    $this->travelTo(CarbonImmutable::parse('2026-10-02'));

    foreach ([['12 kitap oku', 12, 11], ['500 km koş', 500, 375], ['24 blog yazısı yayınla', 24, 9]] as $order => [$title, $target, $current]) {
        Goal::factory()->yearly()->create(['title' => $title, 'target' => $target, 'sort_order' => $order])
            ->progressEntries()->create(['date' => '2026-01-01', 'amount' => $current]);
    }

    $this->get(route('goals.index'))
        ->assertSeeTextInOrder(['12 kitap oku', 'önde', '500 km koş', 'yolunda', '24 blog yazısı yayınla', 'biraz geride']);
});

it('does not break a streak before today is over', function () {
    $chain = Goal::factory()->chain('xxxxx')->create();
    $chain->chainDays()->whereDate('date', today())->delete();

    $this->get(route('goals.index'))->assertSeeText('🔥 4');
});

it('keeps censored chains censored on the home page too', function () {
    goalBoard();

    $this->get(route('home'))
        ->assertDontSee('Ekransız sabahlar')
        ->assertSeeText('sansürlü zincir');
});

it('shows a project its goal chip under the same rules', function (string $visibility, ?string $expected) {
    $goal = Goal::factory()->yearly()->create(['title' => 'Bağlı yıllık hedef', 'visibility' => $visibility]);
    $project = Project::factory()->create(['goal_id' => $goal->id]);

    $response = $this->get(route('projects.show', $project->slug));

    $expected === null
        ? $response->assertDontSeeText('bu proje bir hedefe bağlı')
        : $response->assertSeeText('bu proje bir hedefe bağlı')->assertSee($expected, false);
    $response->assertDontSee($visibility === 'public' ? 'gizli-yok' : 'Bağlı yıllık hedef');
})->with([
    'public' => ['public', 'Bağlı yıllık hedef'],
    'censored' => ['censored', 'sansürlü'],
    'hidden' => ['hidden', null],
]);

describe('chain page', function () {
    it('shows the yearly numbers of a chain', function () {
        goalBoard();

        $this->get(route('goals.chain', 'her-gun-kod'))
            ->assertSeeTextInOrder(['Her gün 30 dk kod', 'şu anki seri', '🔥 23', 'en uzun seri', 'başarı', 'boyunca']);
    });

    it('opens a censored chain under its opaque address without its title', function () {
        $board = goalBoard();

        $this->get(route('goals.chain', 'k-'.$board['secretChain']->id))
            ->assertOk()
            ->assertDontSee('Ekransız sabahlar')
            ->assertSeeText('sansürlü zincir');
    });

    it('does not answer the real slug of a censored chain to visitors, but to close friends', function () {
        $board = goalBoard();

        $this->get(route('goals.chain', $board['secretChain']->slug))->assertNotFound();
        $this->actingAs(User::factory()->close()->create())
            ->get(route('goals.chain', $board['secretChain']->slug))
            ->assertOk()
            ->assertSeeText('Ekransız sabahlar');
    });

    it('returns 404 for a hidden chain', function () {
        $board = goalBoard();

        $this->get(route('goals.chain', $board['hiddenChain']->slug))->assertNotFound();
        $this->get(route('goals.chain', 'k-'.$board['hiddenChain']->id))->assertNotFound();
    });
});

describe('long-term goal page', function () {
    it('shows the yearly goals and chains that serve it, and its story', function () {
        $board = goalBoard();
        $board['product']->updates()->create(['date' => '2026-08-20', 'body' => 'İlk müşteri toplantısı.']);

        $this->get(route('goals.show', 'kendi-urunum'))
            ->assertSeeTextInOrder(['Kendi ürünümü çıkarmak', 'Bu hedefe hizmet edenler', "CoMon'u herkese açık yayınla", 'Her gün 30 dk kod', 'Hikâyesi', 'İlk müşteri toplantısı.']);
    });

    it('returns 404 for a censored long-term goal unless the viewer may read it', function () {
        $board = goalBoard();

        $this->get(route('goals.show', 'k-'.$board['secretLongTerm']->id))->assertNotFound();
        $this->actingAs(User::factory()->member()->create())->get(route('goals.show', $board['secretLongTerm']->slug))->assertNotFound();
        $this->actingAs(User::factory()->close()->create())->get(route('goals.show', $board['secretLongTerm']->slug))
            ->assertOk()
            ->assertSeeText('Gizli bir not.');
    });
});
