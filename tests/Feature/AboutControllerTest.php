<?php

it('tells the story from Ankara to Düren in order', function () {
    $response = $this->get(route('about'));

    $response->assertSeeTextInOrder(['Hikâyem', 'Lise', 'İçişleri Bakanlığı, memur', 'Ankara → Düren', 'Yeniden çırak: Fachinformatiker', 'Yazılım geliştirici, aks-Service GmbH']);
});

it('fills the "now" list from the other sections', function () {
    $response = $this->get(route('about'));

    $response->assertSeeTextInOrder(['Şu an', 'CoMon', 'Severance', 'Her gün 30 dk kod', '23. gündeyim', 'Yapay zekâyla kod yazarken']);
});

it('never puts a censored chain on the "now" list', function () {
    $response = $this->get(route('about'));

    $response->assertDontSee('Ekransız sabahlar');
});
