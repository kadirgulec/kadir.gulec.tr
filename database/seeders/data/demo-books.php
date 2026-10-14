<?php

/*
 * Sample books of the Rafım prototype, for local development only (DemoSeeder).
 * Facts are as published; ratings, dates, reviews and quotes are sample data
 * (the quotes are placeholders, not real lines from the books). Readings are
 * newest first. "reading_list" books are not read yet: drafts in list order.
 */

$placeholder = 'Burada kitaptan altını çizdiğim bir cümle duracak. Demo için yazılmış bir yer tutucu.';

return [
    'books' => [
        [
            'slug' => 'saatleri-ayarlama-enstitusu', 'title' => 'Saatleri Ayarlama Enstitüsü', 'original_title' => null,
            'author' => 'Ahmet Hamdi Tanpınar', 'translator' => null, 'publisher' => 'Dergâh Yayınları', 'year' => 1961,
            'language' => 'Türkçe', 'format' => 'ebook', 'cover_colors' => ['#3b2f1e', '#d9a441'],
            'overview' => 'Hayri İrdal\'ın gözünden, saatleri ayarlamak için kurulan bir enstitünün ve bir dönemin hikâyesi.',
            'rating' => null, 'is_favorite' => false, 'review' => null,
            'quotes' => [[$placeholder, 'Hayri İrdal', '%18']],
            'readings' => [['reading', '2026-09-28', null, 43]],
        ],
        [
            'slug' => 'clean-code', 'title' => 'Clean Code', 'original_title' => null,
            'author' => 'Robert C. Martin', 'translator' => null, 'publisher' => 'Prentice Hall', 'year' => 2008,
            'language' => 'İngilizce', 'format' => 'print', 'cover_colors' => ['#1d2b3a', '#5fa8d3'],
            'overview' => 'Okunabilir, değiştirilebilir kod yazmak üzerine örneklerle dolu bir el kitabı.',
            'rating' => null, 'is_favorite' => false, 'review' => null,
            'quotes' => [],
            'readings' => [['paused', '2026-06-02', null, 71]],
        ],
        [
            'slug' => 'kurk-mantolu-madonna', 'title' => 'Kürk Mantolu Madonna', 'original_title' => null,
            'author' => 'Sabahattin Ali', 'translator' => null, 'publisher' => 'Yapı Kredi Yayınları', 'year' => 1943,
            'language' => 'Türkçe', 'format' => 'ebook', 'cover_colors' => ['#4a1f2b', '#d9707f'],
            'overview' => 'Raif Efendi\'nin defterinde saklı kalan, Berlin\'de başlayan bir aşk hikâyesi.',
            'rating' => 9.0, 'is_favorite' => true,
            'review' => "Bu, demo için yazılmış örnek bir yorum. Gerçek yorumu Kadir yazacak; burada sadece sayfanın nasıl göründüğü deneniyor.\n\nİkinci paragraf: kitabın bende bıraktığı his, hangi sahnede durup düşündüğüm, kime önereceğim gibi şeyler burada olur.\n\n:::alıntı Raif Efendi\nYorumun akışında geçen bir alıntı burada durur. Yer tutucu.\n:::\n\n:::spoiler\nSonla ilgili bir düşünce, markörle kapalı.\n:::",
            'quotes' => [[$placeholder, 'Raif Efendi', '%34'], [$placeholder, null, '%61'], [$placeholder, 'Maria Puder', '%88']],
            'readings' => [['finished', '2026-09-03', '2026-09-21', null]],
        ],
        [
            'slug' => 'korluk', 'title' => 'Körlük', 'original_title' => 'Ensaio sobre a Cegueira',
            'author' => 'José Saramago', 'translator' => 'Aykut Derman', 'publisher' => 'Kırmızı Kedi', 'year' => 1995,
            'language' => 'Türkçe', 'format' => 'ebook', 'cover_colors' => ['#2b2b2b', '#bdbdbd'],
            'overview' => 'Bir şehirde insanlar birer birer kör olmaya başlayınca toplumun dokusu da çözülmeye başlar.',
            'rating' => 8.5, 'is_favorite' => false,
            'review' => "Örnek yorum, ilk paragraf. Bu metin demo için bir yer tutucu.\n\nUzunca bir ikinci paragraf, günlük kartında sadece ilk iki satırın göründüğünü denemek için.",
            'quotes' => [[$placeholder, 'Doktorun karısı', '%52']],
            'readings' => [['finished', '2026-07-20', '2026-08-10', null]],
        ],
        [
            'slug' => 'atomic-habits', 'title' => 'Atomic Habits', 'original_title' => null,
            'author' => 'James Clear', 'translator' => null, 'publisher' => 'Avery', 'year' => 2018,
            'language' => 'İngilizce', 'format' => 'audio', 'cover_colors' => ['#f2efe6', '#e0a526'],
            'overview' => 'Küçük alışkanlıkların zamanla nasıl büyük sonuçlara dönüştüğü üzerine.',
            'rating' => 6.5, 'is_favorite' => false, 'review' => null,
            'quotes' => [],
            'readings' => [['finished', '2026-06-10', '2026-07-02', null]],
        ],
        [
            'slug' => 'die-verwandlung', 'title' => 'Die Verwandlung', 'original_title' => null,
            'author' => 'Franz Kafka', 'translator' => null, 'publisher' => 'Reclam', 'year' => 1915,
            'language' => 'Almanca', 'format' => 'ebook', 'cover_colors' => ['#2f3d1f', '#a8c256'],
            'overview' => 'Gregor Samsa bir sabah uyandığında kendini dev bir böceğe dönüşmüş bulur.',
            'rating' => 7.5, 'is_favorite' => false, 'review' => null,
            'quotes' => [],
            'readings' => [['finished', '2026-05-04', '2026-05-15', null]],
        ],
        [
            'slug' => 'simyaci', 'title' => 'Simyacı', 'original_title' => 'O Alquimista',
            'author' => 'Paulo Coelho', 'translator' => null, 'publisher' => 'Can Yayınları', 'year' => 1988,
            'language' => 'Türkçe', 'format' => 'ebook', 'cover_colors' => ['#5c3b10', '#f0c674'],
            'overview' => 'Endülüslü bir çobanın hazine peşinde çıktığı yolculuk.',
            'rating' => null, 'is_favorite' => false, 'review' => null,
            'quotes' => [],
            'readings' => [['abandoned', '2026-03-20', '2026-04-02', 38]],
        ],
        [
            'slug' => 'tutunamayanlar', 'title' => 'Tutunamayanlar', 'original_title' => null,
            'author' => 'Oğuz Atay', 'translator' => null, 'publisher' => 'İletişim Yayınları', 'year' => 1972,
            'language' => 'Türkçe', 'format' => 'ebook', 'cover_colors' => ['#1e2a44', '#e85d4a'],
            'overview' => 'Selim Işık\'ın intiharının ardından arkadaşı Turgut Özben\'in onun izini sürdüğü roman.',
            'rating' => 10.0, 'is_favorite' => true,
            'review' => 'İkinci okuma üzerine örnek bir yorum. Demo için yer tutucu metin.',
            'quotes' => [[$placeholder, 'Selim Işık', '3. bölüm'], [$placeholder, 'Turgut Özben', '%72']],
            'readings' => [['finished', '2025-12-01', '2026-01-20', null], ['finished', '2019-02-10', '2019-04-28', null]],
        ],
        [
            'slug' => 'tehlikeli-oyunlar', 'title' => 'Tehlikeli Oyunlar', 'original_title' => null,
            'author' => 'Oğuz Atay', 'translator' => null, 'publisher' => 'İletişim Yayınları', 'year' => 1973,
            'language' => 'Türkçe', 'format' => 'print', 'cover_colors' => ['#3a1f3d', '#d77fa1'],
            'overview' => 'Hikmet Benol\'un gecekondusunda kurduğu oyunlar ve gerçeklik arasında gidip gelen bir roman.',
            'rating' => 8.0, 'is_favorite' => false, 'review' => null,
            'quotes' => [],
            'readings' => [['finished', '2025-10-15', '2025-11-30', null]],
        ],
    ],

    'reading_list' => [
        ['slug' => 'huzur', 'title' => 'Huzur', 'author' => 'Ahmet Hamdi Tanpınar', 'year' => 1949, 'language' => 'Türkçe', 'cover_colors' => ['#1f3b4d', '#7fb2c9'], 'note' => 'Saatleri bitirince'],
        ['slug' => 'dune', 'title' => 'Dune', 'author' => 'Frank Herbert', 'year' => 1965, 'language' => 'Türkçe', 'cover_colors' => ['#5a3412', '#e8a04c'], 'note' => 'Ali önerdi'],
        ['slug' => 'kafamda-bir-tuhaflik', 'title' => 'Kafamda Bir Tuhaflık', 'author' => 'Orhan Pamuk', 'year' => 2014, 'language' => 'Türkçe', 'cover_colors' => ['#3d2a4f', '#c9a0dc'], 'note' => null],
        ['slug' => 'thinking-fast-and-slow', 'title' => 'Thinking, Fast and Slow', 'author' => 'Daniel Kahneman', 'year' => 2011, 'language' => 'İngilizce', 'cover_colors' => ['#20323a', '#e2c044'], 'note' => 'iş yerinde önerdiler'],
        ['slug' => 'der-steppenwolf', 'title' => 'Der Steppenwolf', 'author' => 'Hermann Hesse', 'year' => 1927, 'language' => 'Almanca', 'cover_colors' => ['#2e3b2c', '#9fbf7a'], 'note' => 'Almanca okumak için'],
    ],
];
