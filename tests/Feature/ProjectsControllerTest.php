<?php

use App\Enums\ProjectStatus;
use App\Models\Project;
use App\Models\Redirect;
use App\Models\User;

describe('index', function () {
    it('shows the featured project first, then the others in the chosen order', function () {
        Project::factory()->create(['name' => 'Renk Tahmin Oyunu', 'status' => ProjectStatus::Archived, 'sort_order' => 2]);
        Project::factory()->create(['name' => 'Çalışan Portalı', 'status' => ProjectStatus::Live, 'sort_order' => 1]);
        Project::factory()->featured()->create(['name' => 'CoMon', 'status' => ProjectStatus::InProgress, 'sort_order' => 3]);

        $this->get(route('projects.index'))
            ->assertSeeTextInOrder(['şu an üzerinde çalıştığım', 'CoMon', 'YAPIM AŞAMASINDA', 'Çalışan Portalı', 'YAYINDA', 'Renk Tahmin Oyunu', 'ARŞİV']);
    });

    it('leaves drafts and scheduled projects out', function () {
        Project::factory()->create(['name' => 'Görünen Proje']);
        Project::factory()->draft()->create(['name' => 'Taslak Proje']);
        Project::factory()->scheduled()->create(['name' => 'Gelecek Proje']);

        $this->get(route('projects.index'))
            ->assertSeeText('Görünen Proje')
            ->assertDontSeeText('Taslak Proje')
            ->assertDontSeeText('Gelecek Proje');
    });

    it('says so when there is no project yet', function () {
        $this->get(route('projects.index'))->assertOk()->assertSeeText('Henüz burada bir proje yok');
    });
});

describe('show', function () {
    it('renders the case study from Markdown with the devlog', function () {
        $project = Project::factory()->create([
            'body' => "## Hangi problemi çözüyor?\n\nBir ==vurgu== ve kenar notu[^1].\n\n[^1]: Margindeki not.",
        ]);
        $project->devlog()->create(['date' => '2026-09-01', 'body' => 'Sayaç okumalarına **grafik** eklendi.']);

        $this->get(route('projects.show', $project->slug))
            ->assertSeeTextInOrder(['Hangi problemi çözüyor?', 'Margindeki not.', 'Geliştirme günlüğü', 'Sayaç okumalarına grafik eklendi.'])
            ->assertSee('<mark class="marker">vurgu</mark>', false)
            ->assertSee('<strong>grafik</strong>', false);
    });

    it('falls back to the summary for projects without a case study', function () {
        $project = Project::factory()->create(['body' => null]);

        $this->get(route('projects.show', $project->slug))
            ->assertSeeText('Bu proje için henüz uzun bir yazı yok')
            ->assertSeeText('ekran görüntüsü yok')
            ->assertDontSeeText('Geliştirme günlüğü');
    });

    it('returns 404 for an unknown project', function () {
        $this->get(route('projects.show', 'olmayan-proje'))->assertNotFound();
    });

    it('hides drafts from visitors and members', function () {
        $draft = Project::factory()->draft()->create();

        $this->get(route('projects.show', $draft->slug))->assertNotFound();
        $this->actingAs(User::factory()->member()->create())->get(route('projects.show', $draft->slug))->assertNotFound();
    });

    it('shows drafts to the admin with a banner', function () {
        $draft = Project::factory()->draft()->create();

        $this->actingAs(User::factory()->admin()->create())
            ->get(route('projects.show', $draft->slug))
            ->assertOk()
            ->assertSeeText('Taslak · sadece sen görüyorsun');
    });

    it('redirects the old address after the slug of a published project changes', function () {
        $project = Project::factory()->create(['slug' => 'eski-ad']);

        $project->update(['slug' => 'yeni-ad']);

        $this->get('/projeler/eski-ad')->assertRedirect('/projeler/yeni-ad')->assertStatus(301);
    });

    it('does not keep a redirect for an address that is in use again', function () {
        $project = Project::factory()->create(['slug' => 'ilk']);
        $project->update(['slug' => 'ikinci']);
        $project->update(['slug' => 'ilk']);

        expect(Redirect::pluck('to_path', 'from_path')->all())->toBe(['/projeler/ikinci' => '/projeler/ilk']);
        $this->get('/projeler/ilk')->assertOk();
    });
});
