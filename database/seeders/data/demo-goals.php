<?php

/*
 * Sample goals of the design prototype, for local development only (DemoSeeder).
 * A chain pattern lists its days up to today, oldest first: x done, e excused, - missed.
 */

return [
    'chains' => [
        0 => [
            'slug' => 'her-gun-kod',
            'title' => 'Her gün 30 dk kod',
            'visibility' => 'public',
            'parent' => 'kendi-urunum',
            'pattern' => 'xxxxxxxxxxxxxxxxxxxxxxxxxxxxx-xxxx-xxxxxxxxxx-xxxxxexxxxxxxxxxxxxxxxxxxxxxxx-xxxxxxxxxxxxxx-xxxx-xxxx-xxxxxxexxxxxxxxxxxxxxx-xxxxxx-xxxxxxxxxxxxxxxxxxxxxxxxxxexxxxxexxxx--xxxxxxxxx-xxx-x-xxxxxxxxxxexxx-xxxxxxx-xxxx-xxxxx-xxxxxxxxxe--x-x-xxx-xxxxxxxxx-xxxxxxxxxxxxxxxexxxxxxxx',
        ],
        1 => [
            'slug' => 'spor',
            'title' => 'Spor',
            'visibility' => 'public',
            'parent' => 'formda-50',
            'pattern' => 'xx--xxxx-xxx-x-xxx-xxxxxx-xx-xx--xxx-xex-xxxxxxxxxxxx-x-xexxx-x-xx-xxxxxxx-x-xxxx-xx-xxxxxxx-x-xxxxx-xxxxxxxxxx-xxxxex--xx--xxxxxx--x-xxxx-xxxx-xxxxxxxx-exxxxx--xx-xxx-xxx-xxxx-xx-xxxexxxx-xx-xx-xx-x-xxxxxxxxxx-xxxexxxxx-xxxxx-xx-xxx--xxx-xxxxxxxx-xxxxx-xxxxx-xxx-xxxx-xxxxxx',
        ],
        2 => [
            'slug' => 'almanca-okuma',
            'title' => 'Her gün 10 sayfa Almanca',
            'visibility' => 'public',
            'parent' => 'almanca',
            'pattern' => 'x-x-x--xxxxxxxxx-x---x-x-x--x--xex-x-x--x-xx--ex--xxx-xx-xx---xxxxxxxxxxxxxxxx-xxx-xxxxxxxx-xx-xx--xxxxx--xxxxxxx-xx-xxxxxxxx-xxxxxxxxxxxxxxxxxxxxxx-x---x--xxx-xxxx-x-x-e--e--xxx-x--xxx--x--x-x-x-x-x-xxxx-xx-x-xx-xx-xxx--exxxxx-xx-xexxxxx--xx-xx---x-x-x-xxx-xxxxxx--xxxxxx-xx',
        ],
        3 => [
            'slug' => 'gizli-zincir-1',
            'title' => 'Ekransız sabahlar',
            'visibility' => 'censored',
            'parent' => null,
            'pattern' => '-x-xxxxxxxxxx-xxxxxxxxxxx-xxxxx--xxxx-xxxxxxxxxxxxxxxxxx-x--xx--xxxxxxxxx-xxxxxxxxx-x-x-xxxxxxxxxxxxxexxxx-xxxxxxxxxxx-xx--xxxxxx-xxxxxxxxxxxexxx-xxxxx-xxxxx-xxxxxxxxxx-xx-xxxxxxxxx-xxxxxxxxx-xxxxxxxxxxxxxxxexxx-xxxxxxxxxxx-x-xxxxxx--xxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxx',
        ],
        4 => [
            'slug' => 'gizli-zincir-2',
            'title' => 'Tamamen gizli bir alışkanlık',
            'visibility' => 'hidden',
            'parent' => null,
            'pattern' => 'xxxxxxxxxxxxxxxxxx-xxxxxxxxxxxx-x-xxxxxxxxxxxxxxeexxxxxxxexxxxxxxxex-xxxxxxxxxxxxxxx-xxxxxxxxxxxxexxxxxxxxxxxxxxx--xxxxxxxxxx-xxxxxxxxxxxx-xxxxxxxx-xxxxxxxxxxxxxxxxxxxexxxxxxxxxxxxx-xxxxxxxxexxxxxexxxxxx-xxxxxxx-xxxx-xxxxxxxxxxxexxxxxxxxxxxxxxxxxxxxxxxx-xxxxxxxxxxxxxxxxxxxxx',
        ],
    ],
    'yearly' => [
        0 => [
            'slug' => 'comon-yayinla',
            'title' => 'CoMon\'u herkese açık yayınla',
            'measure' => 'milestones',
            'visibility' => 'public',
            'current' => null,
            'target' => null,
            'unit' => null,
            'milestones' => [
                0 => [
                    'title' => 'Kapalı beta',
                    'done' => true,
                ],
                1 => [
                    'title' => 'Hane üyeleri ve davet sistemi',
                    'done' => true,
                ],
                2 => [
                    'title' => 'Sayaç okumaları için grafikler',
                    'done' => true,
                ],
                3 => [
                    'title' => 'Mobil uyum',
                    'done' => false,
                ],
                4 => [
                    'title' => 'Herkese açık yayın',
                    'done' => false,
                ],
            ],
            'achieved_at' => null,
            'parent' => 'kendi-urunum',
            'project' => 'comon',
        ],
        1 => [
            'slug' => '12-kitap',
            'title' => '12 kitap oku',
            'measure' => 'numeric',
            'visibility' => 'public',
            'current' => 10,
            'target' => 12,
            'unit' => 'kitap',
            'milestones' => [
            ],
            'achieved_at' => null,
            'parent' => null,
            'project' => null,
        ],
        2 => [
            'slug' => '500-km',
            'title' => '500 km koş',
            'measure' => 'numeric',
            'visibility' => 'public',
            'current' => 362,
            'target' => 500,
            'unit' => 'km',
            'milestones' => [
            ],
            'achieved_at' => null,
            'parent' => 'formda-50',
            'project' => null,
        ],
        3 => [
            'slug' => '24-yazi',
            'title' => '24 blog yazısı yayınla',
            'measure' => 'numeric',
            'visibility' => 'public',
            'current' => 9,
            'target' => 24,
            'unit' => 'yazı',
            'milestones' => [
            ],
            'achieved_at' => null,
            'parent' => 'turkce-icerik',
            'project' => null,
        ],
        4 => [
            'slug' => 'almanca-c1',
            'title' => 'Almanca C1 sınavını geç',
            'measure' => 'binary',
            'visibility' => 'public',
            'current' => null,
            'target' => null,
            'unit' => null,
            'milestones' => [
            ],
            'achieved_at' => null,
            'parent' => 'almanca',
            'project' => null,
        ],
        5 => [
            'slug' => 'meetup-konusmasi',
            'title' => 'Bir Laravel meetup\'ında konuşma yap',
            'measure' => 'binary',
            'visibility' => 'public',
            'current' => null,
            'target' => null,
            'unit' => null,
            'milestones' => [
            ],
            'achieved_at' => '2026-06-12',
            'parent' => 'turkce-icerik',
            'project' => null,
        ],
        6 => [
            'slug' => 'gizli-yillik-1',
            'title' => 'Kimseye söylemediğim bir hedef',
            'measure' => 'numeric',
            'visibility' => 'censored',
            'current' => 3,
            'target' => 10,
            'unit' => '',
            'milestones' => [
            ],
            'achieved_at' => null,
            'parent' => null,
            'project' => null,
        ],
    ],
    'long_term' => [
        0 => [
            'slug' => 'kendi-urunum',
            'title' => 'Kendi ürünümü çıkarmak',
            'why' => 'Başkasının fikrini değil, kendi fikrimi büyütmek istiyorum. İnsanların gerçekten kullandığı küçük ama dürüst bir ürün.',
            'visibility' => 'public',
            'started_year' => 2024,
            'updates' => [
                0 => [
                    'date' => '2026-09-24',
                    'body' => 'CoMon\'da sayaç grafikleri bitti; kapalı betadaki ilk geri bildirimler çok umut verici.',
                ],
                1 => [
                    'date' => '2026-03-15',
                    'body' => 'CoMon\'un ilk sürümü yayında. Küçük ama benim.',
                ],
                2 => [
                    'date' => '2025-06-01',
                    'body' => 'Bir yan proje yerine tek bir ürüne odaklanmaya karar verdim.',
                ],
            ],
        ],
        1 => [
            'slug' => 'almanca',
            'title' => 'Almancayı Türkçe kadar rahat konuşmak',
            'why' => 'Bir toplantıda kelime aramadan, esprimi çevirmeden konuşabildiğim gün burası gerçekten evim olacak.',
            'visibility' => 'public',
            'started_year' => 2016,
            'updates' => [
                0 => [
                    'date' => '2026-08-20',
                    'body' => 'İlk kez bir müşteri toplantısını baştan sona Almanca yönettim.',
                ],
                1 => [
                    'date' => '2026-01-10',
                    'body' => 'C1 kursuna kaydoldum.',
                ],
                2 => [
                    'date' => '2016-09-01',
                    'body' => 'Düren\'de ilk dil kursu. Bir kelime bile anlamıyordum.',
                ],
            ],
        ],
        2 => [
            'slug' => 'formda-50',
            'title' => '50 yaşına formda girmek',
            'why' => 'Masa başında geçen bir meslekte vücudumu ihmal etmemek. Yaşlandıkça da dağ yürüyüşüne çıkabilen biri olmak.',
            'visibility' => 'public',
            'started_year' => 2025,
            'updates' => [
                0 => [
                    'date' => '2026-09-01',
                    'body' => 'Yılın ilk 300 kilometresi tamam.',
                ],
                1 => [
                    'date' => '2025-11-15',
                    'body' => 'Yarı maraton olmadı, ama düzenli koşmaya başladım.',
                ],
            ],
        ],
        3 => [
            'slug' => 'turkce-icerik',
            'title' => 'Türkçe teknik içerik üreten biri olmak',
            'why' => 'Ben öğrenirken Türkçe kaynak çok azdı. Benden sonra gelenler için o eksikliği biraz kapatmak istiyorum.',
            'visibility' => 'public',
            'started_year' => 2026,
            'updates' => [
                0 => [
                    'date' => '2026-06-12',
                    'body' => 'Bir Laravel meetup\'ında ilk konuşmamı yaptım.',
                ],
                1 => [
                    'date' => '2026-01-05',
                    'body' => 'Bu siteyi Türkçe yazmaya karar verdim.',
                ],
            ],
        ],
        4 => [
            'slug' => 'gizli-uzun-1',
            'title' => 'Çok kişisel bir hedef',
            'why' => 'Bunun nedenini sadece ben biliyorum ve şimdilik öyle kalsın.',
            'visibility' => 'censored',
            'started_year' => 2023,
            'updates' => [
                0 => [
                    'date' => '2026-05-01',
                    'body' => 'Gizli bir not.',
                ],
            ],
        ],
    ],
    'past' => [
        0 => [
            'year' => 2025,
            'title' => 'Fachinformatiker sınavını geç',
            'achieved' => true,
        ],
        1 => [
            'year' => 2025,
            'title' => '10 kitap oku',
            'achieved' => true,
        ],
        2 => [
            'year' => 2025,
            'title' => 'Yarı maraton koş',
            'achieved' => false,
        ],
        3 => [
            'year' => 2025,
            'title' => 'Her ay bir yan proje bitir',
            'achieved' => false,
        ],
        4 => [
            'year' => 2025,
            'title' => 'İlk açık kaynak katkımı yap',
            'achieved' => true,
        ],
    ],
];
