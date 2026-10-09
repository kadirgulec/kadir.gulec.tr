<?php

/*
 * Sample monthly reviews, counted back from today: 1 = last month, 2 = the month before.
 * The numbers are worked out from the demo goals when seeded.
 */
return [
    [
        'months_ago' => 2,
        'summary' => 'Kod zinciri oturdu, Almanca yine ikinci plana düştü.',
        'score' => 6,
        'good' => [
            'Her gün 30 dk kod: neredeyse hiç boş gün yok',
            'Livewire yazısı beklediğimden çok okundu',
        ],
        'hard' => [
            'Almanca okumaya akşamları enerji kalmadı',
            'İki hafta üst üste spora gidemedim',
        ],
        'try' => [
            'Almancayı sabaha, kahvaltıdan önceye almak',
            'Spor çantasını akşamdan hazırlamak',
            'Ayda en az iki yazı',
        ],
        'outcomes' => [],
    ],
    [
        'months_ago' => 1,
        'summary' => 'Spor rutini oturdu, yazı tarafı yine aksadı.',
        'score' => 8,
        'good' => [
            'Altı haftadır her hafta en az iki gün spor',
            '**Clean Architecture**\'ın yarısını bitirdim',
            'Sabah Almancası işe yaradı, zincir kopmadı',
        ],
        'hard' => [
            'Sadece bir blog yazısı çıktı',
            'Laravel dersine hiç vakit ayıramadım',
        ],
        'try' => [
            'Her pazar sabahı 1 saat yazı',
            'Reverb dersini bitirmek',
            'Ayın ilk haftası 4 gün spor',
        ],
        // The outcomes of the month before's "try" items, in order.
        'outcomes' => ['done', 'done', 'not-done'],
    ],
];
