<?php

/*
 * The real projects from the design prototype (descriptions follow their
 * READMEs). Imported once by RealContentSeeder; edited in the admin panel
 * afterwards. The CoMon case study and devlog are sample text, so CoMon
 * starts as a draft.
 */

return [
    0 => [
        'slug' => 'comon',
        'name' => 'CoMon',
        'is_featured' => true,
        'status' => 'in-progress',
        'started_year' => 2025,
        'tagline' => 'Sözleşmeleri, sayaç okumalarını ve ev bütçesini tek yerde tutan, çok kullanıcılı bir ev yönetimi uygulaması.',
        'stack' => [
            0 => 'Laravel',
            1 => 'Livewire',
            2 => 'Alpine.js',
            3 => 'MySQL',
        ],
        'cover' => '/images/projects/comon.webp',
        'gallery' => [
            0 => [
                'url' => '/images/projects/comon-features.webp',
                'caption' => 'özellikler',
            ],
        ],
        'demo_url' => 'https://comon.guelec.eu',
        'repo_url' => null,
        'body' => '## Hangi problemi çözüyor?

Bir evde takip edilmesi gereken şeyler farklı yerlere dağılmış durumda: sözleşmelerin yenileme ve fesih tarihleri bir klasörde, sayaç okumaları bir defterde, harcamalar bir tabloda. CoMon bunları tek bir yerde topluyor ve bir şey gözden kaçmadan önce haber veriyor.

## Neden yaptım?

İhtiyaç duyduğum aracı bulamadım: Ya çok karmaşıktı ya da abonelik istiyordu. CoMon\'u ücretsiz, reklamsız ve verilerin kullanıcıda kaldığı bir araç olarak tasarladım.

## Neler yapabiliyor?

- Sayaçları yönetmek ve okumaları zahmetsizce girmek
- Sözleşmeleri, fiyatlarını ve fesih sürelerini bir bakışta görmek
- Tüketim tahminleri ve değerlendirmeler
- Ev bütçesini kategorilere göre takip etmek
- Süre dolmadan hatırlatma ve uyarılar
- Verileri aileyle paylaşmak
- Verilerin kullanıcıda kalması, dışa aktarılabilmesi

## Teknik kararlar

Uygulama Laravel ve Livewire ile yazıldı; etkileşimlerin çoğu sunucuda kalıyor, Alpine.js sadece küçük arayüz davranışları için kullanılıyor. Bir kullanıcı birden fazla haneye üye olabiliyor ve her hane yalnızca kendi verisini görüyor, bu yüzden her sorgu hane bağlamında çalışıyor.

## Öğrendiklerim

Tek başına bir ürün geliştirmek, kod yazmaktan çok karar vermek demek. Hangi özelliğin bekleyebileceğine karar vermek en zor ve en öğretici kısım oldu.

## Şu anki durum

Kapalı beta tamamlandı, şimdi mobil uyum üzerinde çalışıyorum. Sonraki büyük adım herkese açık yayın.',
        'devlog' => [
            0 => [
                'date' => '2026-09-24',
                'body' => 'Sayaç okumalarına aylık tüketim grafiği eklendi.',
            ],
            1 => [
                'date' => '2026-08-30',
                'body' => 'Hane üyeleri için davet sistemi tamamlandı.',
            ],
            2 => [
                'date' => '2026-07-12',
                'body' => 'Kapalı beta başladı.',
            ],
            3 => [
                'date' => '2026-05-03',
                'body' => 'Sözleşme hatırlatmaları e-postayla gönderilmeye başladı.',
            ],
            4 => [
                'date' => '2026-03-15',
                'body' => 'İlk sürüm: sayaçlar, sözleşmeler ve bütçe.',
            ],
        ],
        'draft' => true,
    ],
    1 => [
        'slug' => 'calisan-portali',
        'name' => 'Çalışan Portalı',
        'is_featured' => false,
        'status' => 'in-progress',
        'started_year' => 2024,
        'tagline' => 'IHK bitirme projem: Bir İK departmanının kâğıt üzerindeki hastalık bildirimi sürecini dijitalleştiren çalışan yönetim portalı.',
        'stack' => [
            0 => 'Laravel',
            1 => 'Livewire',
            2 => 'Filament',
            3 => 'Tailwind CSS',
        ],
        'cover' => null,
        'gallery' => [
        ],
        'demo_url' => null,
        'repo_url' => 'https://github.com/kadirgulec/employee-portal',
        'body' => '## Hangi problemi çözüyor?

Orta ölçekli birçok şirkette çalışan verileri dağınık, iş akışları ise kâğıt üzerinde. Bu projenin hedefi, özellikle telefon ve form ile yürüyen hastalık bildirimi sürecini dijital bir iş akışına, otomatik PDF üretimine ve merkezi panellere taşımaktı.

## Şu anki durum

Proje, Yazılım Geliştirici (IHK) bitirme sınavım için başladı. Mezuniyetten sonra da yeni özellikler ve mimari iyileştirmelerle geliştirmeye devam ediyorum.',
        'devlog' => [
        ],
        'draft' => false,
    ],
    2 => [
        'slug' => 'laravel-newsletter',
        'name' => 'Laravel Newsletter',
        'is_featured' => false,
        'status' => 'live',
        'started_year' => 2026,
        'tagline' => 'Laravel için veritabanı tabanlı, hafif bir bülten paketi: imzalı abonelikten çıkma bağlantıları ve RFC uyumlu e-postalar.',
        'stack' => [
            0 => 'PHP',
            1 => 'Laravel',
        ],
        'cover' => null,
        'gallery' => [
        ],
        'demo_url' => null,
        'repo_url' => 'https://github.com/kadirgulec/laravel-newsletter',
        'body' => null,
        'devlog' => [
        ],
        'draft' => false,
    ],
    3 => [
        'slug' => 'kadir-guelec-eu',
        'name' => 'kadir.guelec.eu',
        'is_featured' => false,
        'status' => 'live',
        'started_year' => 2025,
        'tagline' => 'Almanca ve İngilizce portfolyom ve blogum. 11ty ile üretilen statik bir site: veritabanı yok, sunucu tarafı kod yok.',
        'stack' => [
            0 => '11ty',
            1 => 'Nunjucks',
            2 => 'Markdown',
        ],
        'cover' => '/images/projects/kadir-guelec-eu.webp',
        'gallery' => [
        ],
        'demo_url' => 'https://kadir.guelec.eu',
        'repo_url' => 'https://github.com/kadirgulec/My-11ty-blog',
        'body' => null,
        'devlog' => [
        ],
        'draft' => false,
    ],
    4 => [
        'slug' => 'tic-tac-toe',
        'name' => 'Tic-Tac-Toe (Minimax)',
        'is_featured' => false,
        'status' => 'archived',
        'started_year' => 2026,
        'tagline' => 'Minimax algoritmasını anlamak için yazdığım, yenilmez bir rakibi olan XOX oyunu.',
        'stack' => [
            0 => 'JavaScript',
        ],
        'cover' => null,
        'gallery' => [
        ],
        'demo_url' => null,
        'repo_url' => 'https://github.com/kadirgulec/TicTacToe',
        'body' => null,
        'devlog' => [
        ],
        'draft' => false,
    ],
    5 => [
        'slug' => 'renk-tahmin-oyunu',
        'name' => 'Renk Tahmin Oyunu',
        'is_featured' => false,
        'status' => 'archived',
        'started_year' => 2023,
        'tagline' => 'Verilen RGB koduna bakıp doğru rengi bulmaya çalıştığın küçük bir tarayıcı oyunu; kolay ve zor modlu.',
        'stack' => [
            0 => 'HTML',
            1 => 'CSS',
            2 => 'JavaScript',
        ],
        'cover' => '/images/projects/guess-the-color.webp',
        'gallery' => [
        ],
        'demo_url' => 'https://playguessthecolor.netlify.app/',
        'repo_url' => 'https://github.com/kadirgulec/GuessTheColorGame',
        'body' => null,
        'devlog' => [
        ],
        'draft' => false,
    ],
];
