<?php

it('shows one snippet from every section on the lately board', function () {
    $response = $this->get(route('home'));

    $response->assertSeeTextInOrder([
        'Kuru Otlar Üstüne',
        'Yapay zekâyla kod yazarken kendime koyduğum beş kural',
        'Her gün 30 dk kod',
        'Severance',
        'CoMon',
    ]);
});

it('tells when the last film was watched in Turkish', function () {
    $response = $this->get(route('home'));

    $response->assertSeeText("30 Eylül'de izledim");
});
