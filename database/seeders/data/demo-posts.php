<?php

/*
 * Sample posts of the design prototype, for local development only (DemoSeeder).
 */

return [
    0 => [
        'title' => 'Yapay zekâyla kod yazarken kendime koyduğum beş kural',
        'slug' => 'yapay-zekayla-kod-yazarken-bes-kural',
        'excerpt' => 'Asistan hızlı yazıyor, ama neyin doğru olduğuna hâlâ ben karar veriyorum. Bir yılın sonunda elimde kalan, biraz da acı tecrübeyle öğrendiğim beş kural.',
        'published_at' => '2026-09-28 00:00:00',
        'tags' => [
            0 => 'yapay zekâ',
            1 => 'iş akışı',
        ],
        'is_featured' => true,
        'body' => 'Bir yıldır neredeyse her gün bir yapay zekâ asistanıyla kod yazıyorum. Hız konusunda şikâyetim yok; asıl mesele, hızın beni nereye götürdüğü. ==Asistan ne kadar hızlı yazarsa yazsın, kodun sorumluluğu hâlâ bende.== Bu yazıda, biraz da acı tecrübeyle oturttuğum beş kuralı paylaşıyorum.[^1]

## 1. Önce ben anlayacağım

Asistanın yazdığı bir satırı açıklayamıyorsam o satır commit\'e girmiyor. Kulağa yavaş geliyor ama aslında tersi doğru: Anlamadığım kodu üç hafta sonra hata ayıklarken çok daha pahalıya ödüyorum.

## 2. Testi ben yazarım, ya da en azından okurum

Asistan test yazmakta çok iyi; o kadar iyi ki bazen kodun doğru yaptığını değil, yanlış yaptığını da test ediyor. ==Bir testin neyi kanıtladığını okumadan yeşil ışığa güvenmiyorum.==[^2]

```php
// Kötü: beklenen değer, uygulamanın kendi formülüyle hesaplanıyor.
expect($invoice->total())->toBe($invoice->net() * 1.19);

// İyi: bilinen bir girdi, elle hesaplanmış bir sonuç.
expect($invoice->total())->toBe(119.00);
```

## 3. Küçük adımlar, sık commit

Büyük bir değişikliği tek seferde istemek yerine küçük parçalara bölüyorum. Her parçanın sonunda testler geçiyor ve bir commit atılıyor. Bir şey ters giderse geri dönmek için uzağa gitmem gerekmiyor.

## 4. Bağlamı ben veririm

Projenin kurallarını, isimlendirme alışkanlıklarını ve mimari kararlarını yazılı hâle getirdim. Asistan bunları her seferinde tahmin etmek zorunda kalmayınca daha az hata yapıyor ve yazdığı kod ekibin geri kalanına daha tanıdık geliyor.

- Proje kuralları tek bir dosyada, sürüm kontrolünde.
- Her kural için kısa bir **neden**: Gerekçesi olmayan kural ilk fırsatta çiğneniyor.
- Örnek kod: Anlatmak yerine göstermek.

## 5. Son sözü insan söyler

Bir değişikliği yayına almadan önce mutlaka gözden geçiriyorum; mümkünse bir başkasına da gösteriyorum. Asistan iyi bir yardımcı, ama sorumluluğu devredebileceğim biri değil.

Bu kuralların hiçbiri yeni değil; iyi bir ekipte zaten uygulanan şeyler. Yapay zekâ sadece onları atlamayı çok daha cazip hâle getiriyor.

[^1]: Bu kurallar küçük bir ekipte, çoğunlukla Laravel projelerinde işe yaradı. Sizin bağlamınız farklıysa uyarlayın.
[^2]: En sevdiğim tuzak: beklenen değeri uygulamanın kendi formülüyle hesaplayan test. Kod yanlışsa test de onunla birlikte yanlış.',
    ],
    1 => [
        'title' => 'Livewire\'da tek dosyalı bileşenlere geçerken öğrendiklerim',
        'slug' => 'livewire-tek-dosyali-bilesenler',
        'excerpt' => 'Sınıf ve görünümü aynı dosyada tutmak ilk bakışta dağınık göründü. Birkaç hafta sonra eski düzene dönmek istemediğimi fark ettim.',
        'published_at' => '2026-09-02 00:00:00',
        'tags' => [
            0 => 'livewire',
            1 => 'laravel',
        ],
        'is_featured' => true,
        'body' => 'Yıllarca bir Livewire bileşeni için iki dosya açtım: bir PHP sınıfı ve bir Blade görünümü. Tek dosyalı bileşenler bu ikisini bir araya getiriyor. ==İlk tepkim itiraz oldu, ikinci tepkim rahatlama.==

Küçük bileşenlerde fark hemen hissediliyor: Bir butonun davranışını değiştirmek için iki dosya arasında gidip gelmek yok. Büyük bileşenlerde ise dosya uzadığında bunu bir uyarı işareti olarak görmeyi öğrendim; genellikle bileşenin bölünmesi gerektiğini söylüyor.[^1]

[^1]: Kendime koyduğum sınır: Dosya ekrana sığmıyorsa bileşen büyük demektir.',
    ],
    2 => [
        'title' => 'Alpine.js ile 40 satırda klavye kısayolları',
        'slug' => 'alpine-ile-klavye-kisayollari',
        'excerpt' => 'Bir yönetim panelinde en çok kullanılan üç işlem için klavye kısayolu ekledim. Kütüphane yok, sadece Alpine.',
        'published_at' => '2026-08-11 00:00:00',
        'tags' => [
            0 => 'alpine.js',
            1 => 'javascript',
        ],
        'is_featured' => false,
        'body' => 'Kullanıcılar aynı üç işlemi günde onlarca kez yapıyorsa, fareye uzanmak küçük ama birikmiş bir zaman kaybı. Alpine\'ın `@keydown.window` dinleyicisiyle bunu birkaç satırda çözmek mümkün.

```html
<div
    x-data
    @keydown.window.prevent.ctrl.k="$refs.search.focus()"
    @keydown.window.prevent.ctrl.n="$dispatch(\'open-create-modal\')"
>
    <input x-ref="search" type="search" placeholder="Ara (Ctrl+K)">
</div>
```

Önemli olan kısayolları görünür kılmak: Bir kısayol varsa, butonun yanında küçük bir ipucu olarak yazılmalı. ==Gizli kısayol, olmayan kısayoldur.==',
    ],
    3 => [
        'title' => 'Yeniden çırak olmak: Fachinformatiker eğitimi üzerine notlar',
        'slug' => 'yeniden-cirak-olmak',
        'excerpt' => 'Yıllarca bir devlet dairesinde çalıştıktan sonra Almanya\'da yeniden öğrenci olmak. Kimse bunun kolay olduğunu söylemedi, haklılarmış.',
        'published_at' => '2026-07-20 00:00:00',
        'tags' => [
            0 => 'kariyer',
            1 => 'almanya',
        ],
        'is_featured' => false,
        'body' => 'Meslek değiştirmek, bildiğin her şeyi bırakmak değil; onları yeni bir dile çevirmek. Devlet dairesinde öğrendiğim düzen, belgeleme ve sabır, yazılımda beklediğimden çok daha işime yaradı.

Zor olan teknik kısım değildi. ==Zor olan, yeniden en az bilen kişi olmayı kabullenmekti.== İlk aylarda en çok sorduğum soru "bu neden böyle?" idi; şimdi o soruyu soran stajyerlere aynı sabrı göstermeye çalışıyorum.',
    ],
    4 => [
        'title' => 'Yerel geliştirme ortamımı neden üç kez değiştirdim',
        'slug' => 'gelistirme-ortamimi-neden-uc-kez-degistirdim',
        'excerpt' => 'Her yeni araç bir sorunu çözüp yenisini getirdi. Sonunda aradığımın en hızlı değil, en az düşündüren ortam olduğunu anladım.',
        'published_at' => '2026-06-14 00:00:00',
        'tags' => [
            0 => 'araçlar',
        ],
        'is_featured' => false,
        'body' => 'İyi bir geliştirme ortamı fark edilmeyen ortamdır. Ne zaman ortamımla uğraşmaya başlasam, aslında yapmam gereken işten kaçtığımı fark ettim.

Şu anki kuralım basit: Yeni bir projeyi sıfırdan ayağa kaldırmak on dakikadan uzun sürüyorsa bir şeyler yanlış. ==Hız değil, tekrarlanabilirlik.==',
    ],
    5 => [
        'title' => 'Tailwind 4 ile CSS değişkenleri: bu sitenin tasarım sistemi',
        'slug' => 'tailwind-4-ile-tasarim-sistemi',
        'excerpt' => 'Her bölümün kendi rengi, gece defteri ve elle çizilmiş çizgiler. Hepsi birkaç CSS değişkeni ve bir data niteliğiyle.',
        'published_at' => '2026-05-03 00:00:00',
        'tags' => [
            0 => 'tailwind',
            1 => 'css',
        ],
        'is_featured' => false,
        'body' => 'Bu sitede her sekmenin kendi rengi var. Bileşenler hangi sayfada olduklarını bilmiyor; sadece `bg-section` diyorlar. Rengi seçen, `body` etiketindeki tek bir `data-section` niteliği.

```css
[data-section=\'goals\'] {
    --color-section: var(--color-goals);
    --color-section-ink: var(--color-goals-ink);
}
```

Gece defteri de aynı mantıkla çalışıyor: `.dark` sınıfı geldiğinde aynı değişkenler yeni değerler alıyor. ==Bileşenlerde neredeyse hiç dark: sınıfı yok.==',
    ],
    6 => [
        'title' => '2025\'in sonunda: hedefler, zincirler ve kırılan halkalar',
        'slug' => '2025-hedefler-zincirler',
        'excerpt' => 'Beş hedefin üçü tuttu. Tutmayan ikisini silmek yerine üstlerini karalayıp bıraktım; neden öyle yaptığımı anlatıyorum.',
        'published_at' => '2025-12-29 00:00:00',
        'tags' => [
            0 => 'kişisel',
            1 => 'hedefler',
        ],
        'is_featured' => false,
        'body' => 'Yıl sonu muhasebesi yaparken en kolay şey tutmayan hedefleri listeden sessizce çıkarmak. Bu yıl bunu yapmadım. ==Karalanmış bir hedef, hiç yazılmamış bir hedeften daha dürüst.==

Yarı maraton olmadı, her ay bir yan proje de olmadı. Ama sınavımı geçtim ve ilk açık kaynak katkımı yaptım. Önümüzdeki yıl daha az ama daha net hedefler koyacağım.',
    ],
    7 => [
        'title' => 'Almanya\'da bir Türk yazılımcı olarak toplantılarda konuşmak',
        'slug' => 'toplantilarda-konusmak',
        'excerpt' => 'Teknik olarak hazırdım, dil olarak da fena değildim. Eksik olan, cümlemi bitirmeden söz almaya cesaret etmekti.',
        'published_at' => '2025-11-09 00:00:00',
        'tags' => [
            0 => 'almanya',
            1 => 'kariyer',
        ],
        'is_featured' => false,
        'body' => 'İlk toplantılarımda söyleyeceğimi kafamda kurup Almancasını düzeltene kadar konu çoktan değişmiş oluyordu. ==Mükemmel cümle beklerken söz hakkını kaçırıyordum.==

Çözüm basit ama rahatsız ediciydi: yarım cümleyle söze girmek. Kimse dilbilgisi hatama takılmadı; herkes söylediğim fikre odaklandı.',
    ],
];
