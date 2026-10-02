<?php

describe('index', function () {
    it('shows the featured project first, then the others by status', function () {
        $response = $this->get(route('projects.index'));

        $response->assertSeeTextInOrder(['şu an üzerinde çalıştığım', 'CoMon', 'Çalışan Portalı', 'YAPIM AŞAMASINDA', 'kadir.guelec.eu', 'YAYINDA', 'Renk Tahmin Oyunu', 'ARŞİV']);
    });
});

describe('show', function () {
    it('renders the case study with the devlog and the linked goal', function () {
        $response = $this->get(route('projects.show', 'comon'));

        $response->assertSeeTextInOrder(['Hangi problemi çözüyor?', 'Neler yapabiliyor?', 'Şu anki durum', 'Geliştirme günlüğü', 'Sayaç okumalarına aylık tüketim grafiği eklendi.'])
            ->assertSee('href="'.route('goals.index').'#hedef-comon-yayinla"', false);
    });

    it('falls back to the summary for projects without a case study', function () {
        $response = $this->get(route('projects.show', 'laravel-newsletter'));

        $response->assertSeeText('Bu proje için henüz uzun bir yazı yok')
            ->assertSeeText('ekran görüntüsü yok')
            ->assertDontSeeText('Geliştirme günlüğü');
    });

    it('returns 404 for an unknown project', function () {
        $response = $this->get(route('projects.show', 'olmayan-proje'));

        $response->assertNotFound();
    });
});
