# SEO araştırması: geriye ne kaldı?

Bu dosya kadir.gulec.tr için **2026-10-16** tarihli bir SEO boşluk analizi. Her iddia birincil bir kaynağa bağlı: Google Search Central, web.dev, developer.chrome.com, schema.org, ogp.me, X geliştirici belgeleri, Bing ve IndexNow. Kod durumu hem repodan (`path:satır`) hem de canlı sitenin HTML'inden (`curl`, 2026-10-16) okundu. Kodda değişiklik yapılmadı.

12 Ekim 2026'da birleşen SEO çalışmasıyla zaten yerinde olanlar: JSON-LD (`App\Support\Seo\StructuredData`), canonical, `sitemap.xml`, HSTS, zincir ve yasal sayfalarda `noindex`, notlarda sr-only `h1`. Bunlar burada yeniden araştırılmadı. Yalnızca resmi belgelere göre eksik kalan yerlerine bakıldı.

## Özet

Etki/efor oranına göre sıralı. Her madde ilgili bölüme bağlanıyor.

1. **Search Console + Bing Webmaster Tools kurulumu** (repoda kaydı yok): `kadir.gulec.tr` için domain property, sitemap gönderimi, Bing'e GSC'den içe aktarma. Kod gerekmiyor. → [§5](#5-search-console-ve-bing-webmaster-tools)
2. **Giriş/kayıt sayfaları hem `robots.txt` ile engellenmiş hem de `noindex` değil.** `/login` her sayfadan linkleniyor. Google engellenen URL'yi açıklamasız dizine alabilir ve `noindex`'i hiç göremez. Çözüm: auth layout'a `noindex` eklemek, bu yolları `Disallow`'dan çıkarmak. → [§6.1](#61-robotstxt-ve-giriş-sayfaları)
3. **Boş meta description**: `okuduklarim/aslan-asker-svayk` canlıda `content=""` veriyor. Layout yalnızca `null` için varsayılana düşüyor, boş string için düşmüyor. → [§6.2](#62-title-ve-meta-description)
4. **LCP: film/kitap sayfasında afiş/kapak `loading="lazy"`.** web.dev, LCP görselinin asla lazy yüklenmemesi gerektiğini söylüyor. → [§1](#1-core-web-vitals)
5. **`max-image-preview:large` yok.** Discover'da büyük görsel için gerekiyor. Tek satırlık bir meta. → [§6.3](#63-görsel-seo-ve-max-image-preview)
6. **Site adı tutarsız**: `WebSite.name` = `kadir.gulec.tr` (Google alan adını birincil ad olarak "son çare" sayıyor), title markası ise `Kadir Gülec`. → [§6.4](#64-site-adı-ve-favicon)
7. **Favicon**: Google'ın desteklediği formatlar arasında SVG yok. `rel="icon"` yalnızca SVG. 48px'ten büyük bir PNG/ICO linki eklenmeli. → [§6.4](#64-site-adı-ve-favicon)
8. **Aylık değerlendirme**: ilk değerlendirme yayınlanınca sitemap'teki `/hedefler/aylik` 302 ile yönlenecek. Ayrıca JSON-LD/`article` meta'sı yok ve `h1` her ay aynı. → [§4.3](#43-değerlendirme-aylık-değerlendirmeler)
9. **Öğrendiklerim notlarında JSON-LD düğümü ve `article:published_time` yok.** → [§4.4](#44-öğrendiklerim-notlar)
10. **Görünür yazar satırı / ProfilePage zenginleştirme**: yazının sonundaki "Kadir" imzası `/hakkimda`'ya link vermiyor. `ProfilePage`'de önerilen `image`/`description` yok. → [§6.5](#65-yazar-bilgisi-e-e-a-t-ve-helpful-content)
11. **IndexNow** (Bing, Yandex, Naver, Seznam, Yep, Amazon): yayın/güncelleme/silmede bildirim. Orta efor. → [§5.3](#53-indexnow)
12. **CWV alan verisi**: site küçük olduğu için CrUX'ta muhtemelen veri yok. Önce PSI/GSC ile bakmak, sonra `font-display: block` kararını ölçüyle yeniden tartmak gerekiyor. → [§1](#1-core-web-vitals)
13. Küçük düzeltmeler: `BlogPosting.description` başında boşluk, `og:image:alt` anlamı, gereksiz `twitter:image:alt`, `TVSeries`'i Rich Results Test'te doğrulamak, Article için çoklu en-boy oranlı görsel. → [§3](#3-open-graph--x-twitter-kartları), [§4](#4-yeni-bölümler-için-yapılandırılmış-veri)

---

## 1. Core Web Vitals

### Kaynaklar ne diyor

- **Metrikler ve eşikler**: LCP ≤ 2,5 sn, INP < 200 ms, CLS < 0,1 ([Google: Core Web Vitals](https://developers.google.com/search/docs/appearance/core-web-vitals), son güncelleme 2025-12-10). Search Console raporunun tablosu: LCP iyi ≤2,5 sn / iyileştirilmeli ≤4 sn / kötü >4 sn; INP ≤200 / ≤500 / >500 ms; CLS ≤0,1 / ≤0,25 / >0,25 ([Search Console CWV raporu yardımı](https://support.google.com/webmasters/answer/9205520)).
- **75. persentil**: Sayfa yüklemelerinin 75. persentili ölçülüyor, mobil ve masaüstü ayrı. Üç hedefin üçü de tutmalı ([web.dev: Web Vitals](https://web.dev/articles/vitals), 2024-10-31).
- **Alan verisi ve laboratuvar verisi**: Search Console CWV raporu CrUX'tan, yani gerçek kullanıcıların "field data"sından besleniyor ve son 28 günü gösteriyor. Veri yetersizse "No data available" yazıyor ([GSC yardımı](https://support.google.com/webmasters/answer/9205520)). Lighthouse gibi kullanıcısız lab araçları INP'yi ölçemiyor, onun yerine TBT'ye bakılıyor ([web.dev: Web Vitals](https://web.dev/articles/vitals)). Önceliği belirlemek için "field data is what you should use to prioritize your efforts" ([web.dev: lab ve field farkı](https://web.dev/articles/lab-and-field-data-differences), 2022-07-18).
- **CrUX'a girme şartı**: Sayfa "publicly discoverable" ve "sufficiently popular" olmalı. Eşik açıklanmıyor. `noindex` sayfalar dahil edilmiyor ([CrUX metodolojisi](https://developer.chrome.com/docs/crux/methodology), 2024-06-20).
- **Sıralamadaki ağırlık**: Google tek bir sinyal olmadığını söylüyor: "There is no single signal". Ayrıca: "Google Search always seeks to show the most relevant content, even if the page experience is sub-par." Yani alaka önce geliyor. Sayfa deneyimi, benzer derecede faydalı sayfalar arasında fark yaratıyor ([Google: page experience](https://developers.google.com/search/docs/appearance/page-experience), 2026-09-22). CWV için Google "aligns with what our core ranking systems seek to reward" diyor ama bir ağırlık vermiyor ([CWV sayfası](https://developers.google.com/search/docs/appearance/core-web-vitals)).
- **LCP görseli**: "Never lazy-load your LCP image". `fetchpriority="high"` ise bir iki görselden fazlasına konursa işe yaramıyor ([web.dev: Optimize LCP](https://web.dev/articles/optimize-lcp), 2025-03-31). Ayrıca "Don't lazy-load images that are likely to be in-viewport when the page loads" ([web.dev: lazy loading](https://web.dev/articles/browser-level-image-lazy-loading), 2024-08-13).
- **Fontlar**: `block` için engelleme süresi 2–3 sn ve metin bu sürede görünmez çiziliyor. `swap` metni en az geciktiren değer. `font-display` LCP, FCP ve CLS'yi etkileyebiliyor. Preload ise diğer kaynaklardan bant genişliği çalıyor ([web.dev: font best practices](https://web.dev/articles/font-best-practices), 2022-10-04). Metin LCP öğesiyse ve `font-display` değeri `auto`/`block` dışında bir şeyse, metin yükleme sırasında hep görünür kalıyor ([web.dev: Optimize LCP](https://web.dev/articles/optimize-lcp)).

### Bu kod tabanında durum

- **Afiş ve kapak lazy**: `resources/views/components/site/poster.blade.php:33` ve `resources/views/components/site/book-cover.blade.php:51` her zaman `loading="lazy"`. Bu bileşenler detay sayfasında ilk ekranın en üstünde duruyor (`resources/views/site/watched/show.blade.php:24`, `resources/views/site/books/show.blade.php:30`). Mobilde muhtemelen LCP öğesi bunlar. `aspect-[2/3]` kutusu alanı ayırdığı için CLS riski yok.
- **Fontlar**: Canlı HTML'de 64 `@font-face` ve 9 `preload` var. Fraunces (başlık, `h1`) ile Caveat `font-display: block`, Nunito Sans ile JetBrains Mono `fallback` kullanıyor. Bu bilinçli bir karar: `TASARIM.md:181` ("yedek font titremesini önlemek için"). Metin LCP'li sayfalarda (ana sayfa, yazılar) `h1` en fazla yaklaşık 3 sn görünmez kalabilir.
- Yazı içi görseller `width`/`height` alıyor (`app/Support/Markdown/PolaroidRenderer.php:50-54`), CLS açısından iyi. Hedef görselinin boyutu yok ve sabit bir en-boy kutusu da yok (`resources/views/site/goals/show.blade.php:25`). Bu küçük bir CLS riski.
- Alan verisi ölçülemedi: PSI API anahtarsız kotaya takıldı. Sitenin trafiği CrUX eşiğinin altında olabilir. Bu doğrulanmadı.

### Öneri

1. Poster/book-cover bileşenine bir `eager`/`loading` prop'u eklemek. Detay sayfasında `loading="eager" fetchpriority="high"`, listelerde `lazy` kalsın.
2. Search Console CWV raporuna ve [PageSpeed Insights](https://pagespeed.web.dev/)'a bakmak. "No data" çıkarsa lab sonuçlarını (LCP, CLS, TBT) yalnızca gösterge olarak kullanmak.
3. `font-display: block` kararını ölçüyle yeniden tartmak. Fraunces için `fallback` ya da `optional` LCP'yi kısaltır, bedeli bir an yedek fontun görünmesi. Ayrıca 9 preload'dan hangilerinin ilk ekranda gerçekten gerektiğini kontrol etmek.
4. Önemli olarak: sıralama etkisi küçük. Google alakayı önde tutuyor. Bu iş "bir sonraki küçük adım"dır, acil değil.

## 2. İç linkleme

### Kaynaklar ne diyor

- Google genelde yalnızca `href`'i olan `<a>` öğelerini taranabilir link sayıyor. `onclick` ya da `javascript:` linkleri güvenilir değil. Anchor metni "descriptive, reasonably concise, and relevant" olmalı; "Click here"/"Read more" kötü örnek olarak geçiyor. "Every page you care about should have a link from at least one other page on your site." Sayfa başına ideal bir link sayısı yok ([Google: link best practices](https://developers.google.com/search/docs/crawling-indexing/links-crawlable), 2025-12-10).
- Sayfalandırmada her sayfanın kendi canonical'ı olmalı ("Don't use the first page … as the canonical"). Sayfalar `<a href>` ile sıralı linklenmeli. `rel="next"/"prev"` artık kullanılmıyor ([Google: pagination](https://developers.google.com/search/docs/specialty/ecommerce/pagination-and-incremental-page-loading), 2025-12-10).

### Bu kod tabanında durum

- Durum iyi. Bölüm sekmeleri, footer ve kartlar düz `<a href>`. Livewire `wire:click` yalnızca etkileşimlerde kullanılıyor (takip, yorum), gezinmede yok.
- Sayfalandırma `<a href rel="prev/next">` ile yapılıyor (`resources/views/components/site/pagination.blade.php:9,15`). Sonraki sayfalar kendi adreslerini canonical veriyor (`resources/views/layouts/site.blade.php:21-23`). Bu Google'ın tarifine uyuyor. `rel` zararsız, Google için etkisiz.
- Değerlendirmeler yetim değil: hedefler sayfasındaki kart (`resources/views/site/goals/index.blade.php:36`), ay sekmeleri, eski/yeni linkleri ve arşiv (`resources/views/site/goals/review.blade.php:54,173,183`).
- Notlar eski/yeni bağlantısıyla birbirine zincirli (`resources/views/site/notes/show.blade.php:23-38`). Anchor metni notun ilk satırları, bu da açıklayıcı.

### Öneri

Yapısal bir boşluk yok. İçerik tarafında tek öneri: yazılardan ilgili notlara, notlardan yazılara metin içi linkler. Bu kod değil, editoryal bir alışkanlık.

## 3. Open Graph / X (Twitter) kartları

### Kaynaklar ne diyor

- **ogp.me**: Zorunlu dört özellik `og:title`, `og:type`, `og:image`, `og:url`. `og:image:alt` "A description of what is in the image (not a caption)". Nesne türleri arasında `article` (`published_time`, `modified_time`, `author`, `section`, `tag`), `book` (`book:author`, `book:isbn`, …) ve `video.movie` var ([ogp.me](https://ogp.me/)).
- **X**: Belgenin tablosuna göre `twitter:title` → `og:title`, `twitter:description` → `og:description`, `twitter:image` → `og:image`, `twitter:image:alt` → `og:image:alt` geri düşüşü var. `twitter:card` yoksa ama og:type/title/description varsa "a summary card may be rendered". Görsel 5 MB'tan küçük olmalı, SVG desteklenmiyor. `twitter:site` için belge "Either twitter:site or twitter:site:id is required" diyor ([X Cards markup](https://developer.x.com/en/docs/x-for-websites/cards/overview/markup)). Not: bu sayfa artık `docs.x.com/overview`'a yönleniyor. İçerik [2026-02-01 tarihli arşiv kopyasından](http://web.archive.org/web/20260201012312/https://developer.x.com/en/docs/x-for-websites/cards/overview/markup) okundu.
- **SEO ile ilgisi**: OG doğrudan bir sıralama sinyali olarak belgelenmemiş. Ama Google üç yerde OG'yi kaynak olarak kullandığını yazıyor:
  - title link kaynakları arasında `og:title` ([title links](https://developers.google.com/search/docs/appearance/title-link), 2025-12-10),
  - site adı kaynakları arasında `og:site_name` ([site names](https://developers.google.com/search/docs/appearance/site-names), 2025-12-10),
  - küçük resim seçiminde `og:image` ve `primaryImageOfPage` ([image SEO](https://developers.google.com/search/docs/appearance/google-images), 2026-03-02; [updates, 2026-03-02](https://developers.google.com/search/updates)).
  - Discover da logo yerine temsil edici bir `og:image` istiyor ([Discover](https://developers.google.com/search/docs/appearance/google-discover), 2026-03-09).

### Bu kod tabanında durum

- `resources/views/layouts/site.blade.php:36-53` şunları basıyor: `og:site_name`, `og:locale`, `og:type`, `og:title`, `og:description`, `og:url` (=canonical), `og:image` (sayfaya özel 1200×630 PNG), `og:image:width/height/alt`, yazılarda `article:published_time/modified_time`, `twitter:card=summary_large_image`, `twitter:image:alt`. Dört zorunlu OG özelliğinin hepsi var.
- `og:image:alt` = "`{başlık} · kadir.gulec.tr`" (`site.blade.php:45,53`). ogp.me'nin tarifine göre bu görselin içeriğini anlatmıyor, bir başlık/etiket gibi duruyor.
- `twitter:image:alt` `og:image:alt` ile aynı. X zaten geri düşüyor, yani bu satır gereksiz ama zararsız.
- Notlar `og-type="article"` veriyor ama `published-at` geçmiyor (`resources/views/site/notes/show.blade.php:3`), dolayısıyla `article:published_time` yok. Film/dizi/kitap sayfaları `website` (`watched/show.blade.php:18`, `books/show.blade.php:24`).
- `og:description` boş kalabiliyor (kitap örneği, §6.2).

### Öneri

- `og:image:alt` için görselin içeriğini anlatan bir metin: örneğin "`{başlık}` yazılı defter sayfası kartı". `twitter:image:alt` satırını silmek isteğe bağlı.
- Notlarda `:published-at="$note['publishedAt']"` geçmek. Kitaplar için `og:type=book` + `book:author/isbn`, filmler için `video.movie`/`video.tv_show` yalnızca sosyal önizlemeyi etkiler. Düşük öncelik.
- `twitter:site`: X hesabı yoksa eklenemez. Kartın bu olmadan da çıktığı yaygın bir gözlem ama resmi belgede yazmıyor (doğrulanmadı).

## 4. Yeni bölümler için yapılandırılmış veri

Genel kurallar: JSON-LD öneriliyor ve yapılandırılmış verinin sıralamayı etkilediği söylenmiyor ([intro](https://developers.google.com/search/docs/appearance/structured-data/intro-structured-data), 2025-12-10). Sayfada görünmeyen içerik işaretlenmemeli. Zorunlu alanı eksik öğe rich result'a giremiyor. Görsel URL'leri taranabilir olmalı. Rich result garantisi yok ([genel yönergeler](https://developers.google.com/search/docs/appearance/structured-data/sd-policies), 2026-07-10).

**Google'da güncel durum** ([search gallery](https://developers.google.com/search/docs/appearance/structured-data/search-gallery), 2026-06-15; [updates](https://developers.google.com/search/updates)): Galeride 25 özellik var: Article, Breadcrumb, Carousel, Movie, Profile page, Review snippet, Software app, Image metadata ve diğerleri. FAQ rich result 2026-05-07'de gösterimden kalktı, belgesi 2026-06-15'te silindi. Practice problem belgesi 2026-01-06'da silindi. Haziran 2025'te Course info, Claim review, Estimated salary, Learning video, Special announcement ve Vehicle listing kaldırıldı ([blog, 2025-06](https://developers.google.com/search/blog/2025/06/simplifying-search-results), [blog, 2025-11](https://developers.google.com/search/blog/2025/11/update-on-our-efforts)). Book actions ise geri çekilmedi.

### 4.1 Rafım: izlediklerim (Movie / TVSeries + Review)

- **schema.org izin veriyor**: `Review.itemReviewed` herhangi bir `Thing` olabilir. `TVSeries`, `CreativeWorkSeries`'in alt türü ([schema.org/TVSeries](https://schema.org/TVSeries)).
- **Google destekliyor**: Review snippet için `itemReviewed` türleri Book, Course, CreativeWorkSeason, CreativeWorkSeries, Episode, Event, Game, HowTo, LocalBusiness, MediaObject, **Movie**, MusicPlaylist, MusicRecording, Organization, Product, Recipe, SoftwareApplication. Zorunlu alanlar: `author` (geçerli ad, 100 karakterden kısa), `itemReviewed.name`, `reviewRating.ratingValue`. `bestRating`/`worstRating` yazılmazsa 5 ve 1 varsayılıyor ([review snippet](https://developers.google.com/search/docs/appearance/structured-data/review-snippet), 2026-09-08). `TVSeries` listede adıyla geçmiyor, yalnızca üst türü `CreativeWorkSeries` var. Google'ın alt türü kabul edip etmediği belgede yazmıyor.
- **Self-serving kuralı**: Yalnızca **LocalBusiness/Organization** için geçerli ("If the entity that's being reviewed controls the reviews about itself…"). Kadir'in başkasına ait film ve kitapları puanlaması bu kurala girmiyor. 2026-07-24'te eklenen yeni kural da sahte ve açıklanmamış teşvikli yorumları yasaklıyor ([updates](https://developers.google.com/search/updates); [review snippet](https://developers.google.com/search/docs/appearance/structured-data/review-snippet)). Bu sitede sorun değil. Değerlendirme içeriği sayfada görünür olmalı. Tek bir öğe hakkında olmalı, bir liste hakkında değil.
- **Movie carousel**: Film listeleri için, "only available on mobile devices". `image` zorunlu, 6:9 oranında ve **.jpg, .png ya da .gif** olmalı ([Movie](https://developers.google.com/search/docs/appearance/structured-data/movie), 2026-09-08). Sitedeki afişler `.webp` (canlıda `/storage/posters/…-480.webp`), yani mevcut haliyle uygun değil.
- **Durum**: `StructuredData::review()` (`app/Support/Seo/StructuredData.php:139-170`) Google'ın zorunlu alanlarını karşılıyor. `bestRating: 10` ve `worstRating: 0` doğru şekilde açık yazılmış. Puan yoksa düğüm hiç basılmıyor (`:141`). Canlıda Mad Men için Review yok, demek ki puanı yok. Tutarlı. `datePublished` izleme tarihi (`:166`), yorumun yazıldığı tarih değil. Ufak bir anlam farkı.
- **Öneri**: Bir dizi sayfasını [Rich Results Test](https://search.google.com/test/rich-results) ile denemek ve `TVSeries`'in tanınıp tanınmadığına bakmak. Tanınmazsa `"@type": ["TVSeries", "CreativeWorkSeries"]` denenebilir (doğrulanmadı). Movie carousel'ı hedeflemeye değmez.

### 4.2 Rafım: okuduklarım (Book + Review)

- **Google**: `Book`, review snippet için desteklenen bir tür (yukarıdaki liste). "Book actions" ise yalnızca ilgi formunu doldurup onboard edilmiş, geniş kataloglu kitap sağlayıcıları için ([Book actions](https://developers.google.com/search/docs/appearance/structured-data/book), 2025-12-10). Kişisel blog için değil.
- **Durum**: `StructuredData::bookReview()` (`app/Support/Seo/StructuredData.php:178-210`) uygun. `isbn`, `author`, `translator`, `publisher` dolu. Örnek kitapta puan olmadığı için düğüm yok.
- **Öneri**: Kod değişikliği gerekmiyor. Asıl boşluk boş meta description (§6.2).

### 4.3 Değerlendirme (aylık değerlendirmeler)

- **Google**: Article/BlogPosting için zorunlu özellik yok. Önerilenler `author` (+`author.url`), `datePublished`, `dateModified`, `headline`, `image`. Görsel için 16:9, 4:3 ve 1:1 oranlarında, en az 50K piksel olan birden çok görsel öneriliyor ([Article](https://developers.google.com/search/docs/appearance/structured-data/article), 2026-09-08). Tarihler görünür ve tutarlı olmalı, gelecek tarih olmamalı ([byline dates](https://developers.google.com/search/docs/appearance/publication-dates)).
- **Durum**:
  - `resources/views/site/goals/review.blade.php:16-22` ne `structured-data`, ne `og-type="article"`, ne de `published-at` geçiyor.
  - `h1` her ay aynı: "Her ayın sonunda dürüst bir bakış." (`review.blade.php:28-34`). Google title link'i üretirken `h1`'i de kaynak olarak kullanıyor ([title links](https://developers.google.com/search/docs/appearance/title-link)). `<title>` ayı içerdiği için sorun büyük değil.
  - **Yönlendirme**: `/hedefler/aylik`, yayınlanmış değerlendirme varsa `redirect()->route(...)` ile, yani **302** ile son aya gidiyor (`app/Http/Controllers/MonthlyReviewsController.php:20-22`). Aynı URL sitemap'e de ekleniyor (`app/Http/Controllers/SitemapController.php:39-43`). Google'a göre geçici yönlendirme canonical sinyali sayılmıyor ([redirects](https://developers.google.com/search/docs/crawling-indexing/301-redirects), 2026-04-14). Sitemap'e "arama sonuçlarında görmek istediğin URL'ler" konmalı ([build sitemap](https://developers.google.com/search/docs/crawling-indexing/sitemaps/build-sitemap), 2026-07-08). Bing de yönlenen URL'leri sitemap'ten çıkarmayı öneriyor ([Bing blog, 2025-07-31](https://blogs.bing.com/webmaster/2025/7/Keeping-Content-Discoverable-with-Sitemaps-in-AI-Powered-Search/)). İlk değerlendirme 1 Kasım'da gelecek. O zamana kadar sorun yok.
- **Öneri**:
  1. `StructuredData::blogPosting()`'e benzer bir `reviewPosting()`: `BlogPosting`, `headline` "Ekim 2026 değerlendirmesi", `datePublished` = `published_at`, `dateModified`, `image` = OG, `author` = `#kadir`.
  2. Layout'a `og-type="article"` ve `published-at`.
  3. `h1`'e ya da hemen altına ay adını koymak.
  4. Yönlendirme sürerken `/hedefler/aylik`'i sitemap'ten çıkarmak. 302 doğru kod (hedef her ay değişiyor), değiştirmeye gerek yok.

### 4.4 Öğrendiklerim (notlar)

- **Google**: Article/BlogPosting'de zorunlu alan yok (yukarıda). `DiscussionForumPosting`/`SocialMediaPosting` kullanılmamalı: "Don't use this markup for content that's primarily authored by the publishers of the website" ([discussion forum](https://developers.google.com/search/docs/appearance/structured-data/discussion-forum), 2026-09-08). schema.org'da "not"a özel bir tür bulunmadı.
- **Durum**: `resources/views/site/notes/show.blade.php:3` `structured-data` geçmiyor. Canlıda yalnızca WebSite, Person ve BreadcrumbList var.
- **Öneri**: Notlar için `BlogPosting`: `headline` = `$title` (notun ilk kelimeleri), `datePublished`, `image` = OG, `author`, `keywords` = etiket. Rich result beklenmemeli. Yine de Google'ın sayfayı "yazarı belli, tarihli bir yazı" olarak anlamasına yardım eder.

### 4.5 Diğer sayfalar

- **Projeler**: Software App rich result `name`, `offers.price` ve `aggregateRating` **ya da** `review` istiyor ([software app](https://developers.google.com/search/docs/appearance/structured-data/software-app), 2026-09-08). Kendi uygulamasına kendisinin puan vermesi anlamsız. Bu yüzden önerilmiyor. İstenirse rich result beklemeden `SoftwareApplication`/`CreativeWork` eklenebilir.
- **Küçük düzeltme**: `BlogPosting.description` canlıda boşlukla başlıyor (`" Hetzner'de…"`). Layout meta etiketini `trim` ediyor (`site.blade.php:17`) ama JSON-LD etmiyor (`app/Support/Seo/StructuredData.php:122`).
- **BlogPosting görseli** tek bir 1200×630 OG (`StructuredData.php:123`). Google çoklu oran öneriyor ama zorunlu değil. Düşük öncelik.

## 5. Search Console ve Bing Webmaster Tools

### 5.1 Google Search Console

- **Property**: Domain property tüm alt alan adlarını ve protokolleri kapsıyor ve yalnızca DNS ile doğrulanıyor. `m.example.com` gibi bir alt alan adı da domain property olabiliyor ([GSC: property ekleme](https://support.google.com/webmasters/answer/34592)). `kadir.gulec.tr` için DNS TXT kaydıyla doğrulanan bir domain property uygun.
- **Sitemap**: Search Console'dan ya da `robots.txt`'deki `Sitemap:` satırıyla gönderilebiliyor. Gönderim yalnızca bir ipucu. `<priority>` ve `<changefreq>` yok sayılıyor. `<lastmod>` "consistently and verifiably accurate" olduğunda kullanılıyor. RSS 2.0 ve Atom 1.0 akışları da sitemap olarak gönderilebiliyor, ama yalnızca son URL'leri taşıyorlar ([build sitemap](https://developers.google.com/search/docs/crawling-indexing/sitemaps/build-sitemap), 2026-07-08).
- **Page indexing raporu**: "You should not expect *all* URLs on your site to be indexed". "Page with redirect", "Alternate page with proper canonical tag" ve "URL marked 'noindex'" çoğu zaman bilerek yapılan dışlamalar. "Crawled/Discovered – currently not indexed" için genelde bir şey yapmak gerekmiyor. Soft 404 için gerçek bir 404 dönülmeli ([page indexing raporu](https://support.google.com/webmasters/answer/7440203)).
- **URL Inspection**: Dizindeki sürümle canlı test ayrı şeyler. "Request indexing"in günlük kotası var ve dizine girmeyi garanti etmiyor. Çok sayıda URL için sitemap öneriliyor. Tespit edilen yapılandırılmış veri de gösteriliyor ([URL inspection](https://support.google.com/webmasters/answer/9012289)).
- **Bu kod tabanında**: `robots.txt` sitemap'i bildiriyor (`app/Http/Controllers/SitemapController.php:98`). Sitemap 35 URL içeriyor, `lastmod` `updated_at`'ten geliyor ve `priority`/`changefreq` yok. Bu iyi. `updated_at`'i başka modellerden güncelleyen bir `touches` ya da sayaç bulunamadı, yani `lastmod` muhtemelen dürüst. 404'ler gerçek 404 (canlıda denendi). `http`→`https` ve sondaki `/` için 301 var. Repoda Search Console doğrulamasına dair kayıt yok (`DEPLOY.md`'de de yok).
- **Öneri**: Domain property açmak, `sitemap.xml`'i göndermek. İsteğe bağlı olarak üç Atom akışını da göndermek. Haftalık olarak Page indexing raporuna bakmak. Yeni bir yazıdan sonra URL Inspection → Request indexing.

### 5.2 Bing Webmaster Tools

- Siteler Search Console'dan içe aktarılabiliyor ve otomatik doğrulanıyor. Bing sahipliği periyodik olarak yeniden kontrol ediyor ve yeni sitemap'leri de alıyor ([Bing blog, 2019-09](https://blogs.bing.com/webmaster/september-2019/Import-sites-from-Search-Console-to-Bing-Webmaster-Tools)). Sitemap `robots.txt`'den ya da doğrudan gönderilebiliyor. `lastmod` sitemap'in üretildiği zamanı değil, içeriğin gerçek değişme zamanını göstermeli. `changefreq`/`priority` yok sayılıyor ([Bing blog, 2025-07-31](https://blogs.bing.com/webmaster/2025/7/Keeping-Content-Discoverable-with-Sitemaps-in-AI-Powered-Search/)).
- Not: [Bing Webmaster Guidelines](https://www.bing.com/webmasters/help/webmaster-guidelines-30fba23a) sayfası JavaScript ile yüklendiği için metni doğrudan okunamadı. Bing'e dair iddialar yalnızca okunabilen blog yazılarına ve IndexNow sayfalarına dayanıyor.

### 5.3 IndexNow

- **Protokol**: Tek URL için `GET https://<searchengine>/indexnow?url=…&key=…`, toplu gönderim için JSON `POST` (`host`, `key`, `keyLocation`, `urlList`; gönderim başına en fazla 10.000 URL). Anahtar 8–128 karakter. Kökte `{key}.txt` dosyası barındırmak şiddetle öneriliyor. 200/202/400/403/422/429 kodları var. Bildirim içerik eklenince, güncellenince **ve silinince** yapılmalı ([IndexNow documentation](https://www.indexnow.org/documentation)). Katılan motorlar Amazon, Bing, Naver, Seznam, Yandex ve Yep. Bir motora yapılan gönderim hepsiyle paylaşılıyor. Dizine girme garantisi yok. Değişmemiş URL'ler tekrar gönderilmemeli. Sitemap yine öneriliyor ([IndexNow FAQ](https://www.indexnow.org/faq)). Bing de ikisini birlikte kullanmayı öneriyor ([Bing blog, 2025-07-31](https://blogs.bing.com/webmaster/2025/7/Keeping-Content-Discoverable-with-Sitemaps-in-AI-Powered-Search/); [Bing IndexNow](https://www.bing.com/indexnow/getstarted)).
- **Google**: IndexNow'ın resmi belgelerinde Google geçmiyor. Google Search Central'da da IndexNow'dan söz edilmiyor.
- **Bu kod tabanında**: Yok.
- **Öneri** (orta efor): Anahtar dosyasını bir route ya da `public/` altında sunmak. Post, Note, Book, Watchable, MonthlyReview ve Project yayınlandığında, güncellendiğinde ya da silindiğinde kuyruğa giden küçük bir job ile `api.indexnow.org`'a POST atmak. Bağımlılık gerekmiyor, Laravel `Http` client yeter. Taslaklar gönderilmemeli.

## 6. 2026'da belgelenmiş diğer konular

### 6.1 robots.txt ve giriş sayfaları

- **Kaynak**: robots.txt "is not a mechanism for keeping a web page out of Google". Engellenen bir URL başka yerden linkleniyorsa açıklamasız olarak dizine girebilir ([robots.txt intro](https://developers.google.com/search/docs/crawling-indexing/robots/intro), 2025-12-10). Robots meta kuralları yalnızca tarayıcı sayfaya erişebiliyorsa okunuyor. robots.txt ile engellenen sayfadaki kurallar "will be ignored" ([robots meta](https://developers.google.com/search/docs/crawling-indexing/robots-meta-tag), 2026-03-24). Page indexing yardımı da dizinden çıkarmak için robots.txt engelini kaldırıp `noindex` kullanmayı öneriyor ([page indexing](https://support.google.com/webmasters/answer/7440203)).
- **Durum**:
  - `SitemapController.php:85-96` şu yolları `Disallow` ediyor: `/login`, `/register`, `/forgot-password`, `/reset-password`, `/two-factor-challenge`.
  - Auth layout `x-layouts::site`'ı `noindex` olmadan sarıyor (`resources/views/layouts/auth.blade.php:12`). Canlıda `/login` sayfasında robots meta yok ama JSON-LD var.
  - `/login` her sayfanın footer'ından linkleniyor (`resources/views/layouts/site.blade.php:145`), ayrıca yorum ve takip bileşenlerinden.
  - Sonuç: bu URL'ler "Indexed, though blocked by robots.txt" olarak görünebilir.
- **Öneri**: `auth.blade.php:12`'ye `noindex` geçmek. Login/register/forgot/reset/two-factor yollarını `Disallow` listesinden çıkarmak, böylece Google `noindex`'i görebilsin. `/admin`, `/hesap` ve `/user/` zaten giriş gerektiriyor. Onlar kalabilir.

### 6.2 Title ve meta description

- **Kaynak**: Title linkinin kaynakları `<title>`, ana görsel başlık, `h1`, `og:title`, belirgin metin, sayfaya gelen anchor'lar ve `WebSite` verisi. Başlıklar benzersiz ve kısa olmalı, şablon tekrarından kaçınılmalı. Marka bir ayraçla ayrılabilir ([title links](https://developers.google.com/search/docs/appearance/title-link), 2025-12-10). Snippet çoğunlukla sayfa içeriğinden üretiliyor. Meta description daha iyi anlatıyorsa o kullanılıyor. Uzunluk sınırı yok. Her sayfaya benzersiz bir açıklama öneriliyor. Büyük siteler için programatik üretim uygun ([snippet](https://developers.google.com/search/docs/appearance/snippet), 2026-04-20).
- **Durum**:
  - Başlık kalıbı `{başlık} · Kadir Gülec` (`site.blade.php:18`). Bu Google'ın tarifine uyuyor.
  - **Hata**: `$description ?? "…varsayılan…"` (`site.blade.php:17`) yalnızca `null`'ı yakalıyor. `BookContent.php:180` ve `WatchedContent.php:191`, `meta_description` ve `overview` ikisi de boşsa `""` döndürüyor. Canlıda `okuduklarim/aslan-asker-svayk` `<meta name="description" content="">` ve boş `og:description` veriyor. Hedefler (`goals/show.blade.php:3`, `strip_tags($goal['why'])`) aynı riski taşıyor.
  - Film açıklamaları TMDB özetinden geliyor (`WatchedContent.php:191`), Kadir'in kendi metni değil. Google meta description'ı daha iyi bulursa onu, bulmazsa sayfa içeriğini kullanıyor. Kadir'in yorumundan bir alıntı ("reviewExcerpt") daha benzersiz bir açıklama olur.
- **Öneri**:
  1. Layout'ta `filled($description) ? … : varsayılan` kullanmak. Ya da içerik sınıflarında sırayla `meta_description`, yorum alıntısı, `overview` ve "{başlık}: Kadir'in notları" gibi bir son yedek.
  2. Film ve kitaplarda yorum alıntısını özetin önüne almak.

### 6.3 Görsel SEO ve `max-image-preview`

- **Kaynak**: Alt metin kısa ve açıklayıcı olmalı, keyword doldurulmamalı. Google CSS arka plan görsellerini dizine almıyor. `srcset` kullanılıyorsa `src` yedeği olmalı. Desteklenen formatlar arasında WebP ve AVIF de var ([image SEO](https://developers.google.com/search/docs/appearance/google-images), 2026-03-02). Discover için en az 1200 px genişlik, 300K pikselden fazla ve 16:9 oranında görsel isteniyor, "Enabled by the max-image-preview:large setting" ([Discover](https://developers.google.com/search/docs/appearance/google-discover), 2026-03-09). `max-image-preview:large` "A larger image preview, up to the width of the viewport" demek ([robots meta](https://developers.google.com/search/docs/crawling-indexing/robots-meta-tag)).
- **Durum**:
  - Alt metinler iyi: `"{başlık} afişi"`, `"{başlık} kapağı"`, proje ekran görüntülerinde başlık ve açıklama, yazı görsellerinde Markdown alt metni.
  - Hedef görselleri `alt=""` (`resources/views/site/goals/show.blade.php:25`, `goals/index.blade.php:270`). Bu görseller içerik taşıyorsa açıklama yazılmalı.
  - Üretilen posterlerde renk geçişi CSS arka planıyla yapılıyor. Bunlar zaten içerik görseli değil.
  - İndekslenen sayfalarda robots meta hiç yok (`site.blade.php:63-68`), yani `max-image-preview` varsayılanda kalıyor. OG görselleri 1200×630 (≈756K piksel), Discover'ın eşiğini geçiyor.
- **Öneri**: İndekslenen sayfalara `<meta name="robots" content="max-image-preview:large" />` eklemek, istenirse `max-snippet:-1` ile birlikte. Image sitemap gerekmiyor: görseller `<img src>` ile zaten bulunabiliyor.

### 6.4 Site adı ve favicon

- **Kaynak, site adı**: Site adı alan adı ve **alt alan adı** düzeyinde destekleniyor. `WebSite` verisi **ana sayfada** olmalı, `name` ve `url` zorunlu, `alternateName` isteğe bağlı. Ad ana sayfada tutarlı kullanılmalı. `og:site_name`, `<title>` ve başlıklar da kaynak olarak kullanılıyor. Alan adını birincil ad yapmak "only … as a last resort" olmalı. Alan adı yedek ad olarak küçük harfle `alternateName`'e konabilir ([site names](https://developers.google.com/search/docs/appearance/site-names), 2025-12-10).
- **Durum, site adı**: `WebSite.name` = `kadir.gulec.tr`, `alternateName` = `Kadir Gülec` (`app/Support/Seo/StructuredData.php:54-55`). `og:site_name` = `kadir.gulec.tr` (`site.blade.php:36`). Title markası ve ana sayfa başlığı ise "Kadir Gülec · Düren'de bir yazılımcının dijital defteri" (`site.blade.php:18`). Google'ın tarifinin tersi: alan adı birincil, kişi adı yedek.
- **Öneri, site adı**: Bu bir ürün kararı, Kadir'e sorulmalı. Belgeye uyan seçenek `name: "Kadir Gülec"` (ya da "Kadir Gülec'in dijital defteri"), `alternateName: ["kadir.gulec.tr"]` ve `og:site_name` ile title'ı aynı ada çekmek.
- **Kaynak, favicon**: `<link rel="icon">` ana sayfada olmalı, kare, en az 8×8 px, 48×48'den büyüğü öneriliyor. Desteklenen formatlar: "BMP, GIF, ICO, PNG, JPEG, PPM, and TIFF". **SVG listede yok.** `rel` değeri olarak `icon`, `shortcut icon`, `apple-touch-icon` ve `apple-touch-icon-precomposed` kabul ediliyor. Site (hostname) başına tek favicon ([favicon](https://developers.google.com/search/docs/appearance/favicon-in-search), 2026-08-28). Bu tarihli güncelleme yalnızca format listesini açık yazdı ([updates](https://developers.google.com/search/updates)).
- **Durum, favicon**: `site.blade.php:81` yalnızca `favicon.svg`. `:82` `apple-touch-icon.png` (180×180 PNG). Google muhtemelen bunu kullanır ama bu doğrulanmadı. `public/favicon.ico` 16 ve 32 px, linklenmemiş.
- **Öneri, favicon**: `<link rel="icon" type="image/png" sizes="192x192" href="/icons/icon-192.png">` eklemek. Dosya zaten var. SVG tarayıcılar için kalsın.

### 6.5 Yazar bilgisi, E-E-A-T ve helpful content

- **Kaynak**: "We strongly encourage adding accurate authorship information, such as bylines". Byline yazar hakkında daha fazla bilgiye götürmeli. E-E-A-T "isn't a specific ranking factor". "trust is most important". İçerik değişmeden tarihi değiştirmek bir uyarı işareti. Belirli bir kelime sayısına yazmak da öyle ("No, we don't") ([helpful content](https://developers.google.com/search/docs/fundamentals/creating-helpful-content), 2026-10-05). Article verisinde `author.url` ya da `sameAs` "strongly recommend" ediliyor ([Article](https://developers.google.com/search/docs/appearance/structured-data/article)). ProfilePage, "About Me" sayfasını geçerli bir kullanım olarak sayıyor. Önerilen alanlar `mainEntity.image`, `description`, `sameAs`, `dateCreated`, `dateModified` ([ProfilePage](https://developers.google.com/search/docs/appearance/structured-data/profile-page), 2026-09-08).
- **Durum**:
  - Yazının sonunda el yazısıyla "Kadir" ve tarih var ama link yok (`resources/views/site/posts/show.blade.php:30-34`). `/hakkimda` yalnızca footer'da linkli (`site.blade.php:139`).
  - `Person` düğümünde `url` = `/hakkimda` ve `sameAs` var (`StructuredData.php:64-76`). Bu iyi.
  - `ProfilePage`'de yalnızca `mainEntity` var (`StructuredData.php:217-226`). `image`, `description` ve `dateModified` yok.
- **Öneri**: İmzayı "Kadir Gülec" olarak `/hakkimda`'ya linklemek. `Person`'a hakkımda sayfasındaki fotoğrafı (`image`) ve kısa bir `description` eklemek. `ProfilePage`'e `dateModified` eklemek.

### 6.6 Dil, hreflang, ccTLD

- **Kaynak**: "Google uses the visible content of your page to determine its language. We don't use any code-level language information such as `lang` attributes". hreflang yalnızca aynı içeriğin başka dil ve bölge sürümleri için gerekiyor. ccTLD bir ülkeyi hedeflediğine dair "strong signal" sayılıyor ([multi-regional](https://developers.google.com/search/docs/specialty/international/managing-multi-regional-sites), 2025-12-10).
- **Durum**: `<html lang="tr">` (`site.blade.php:28`) ve `og:locale=tr_TR` var. Site tek dilli. `.tr` ccTLD Türkiye sinyali veriyor. Okur Düren/Almanya'daki Türkçe konuşanlarsa bile içerik dili yeterli bir sinyal.
- **Öneri**: Bir şey yapmaya gerek yok. hreflang eklemek yanlış olur. `lang="tr"` erişilebilirlik için kalmalı.

### 6.7 RSS/Atom akışları

- **Kaynak**: Google Atom 1.0 ve RSS 2.0 akışlarını sitemap olarak kabul ediyor, ama yalnızca son URL'leri taşıyorlar ([build sitemap](https://developers.google.com/search/docs/crawling-indexing/sitemaps/build-sitemap)).
- **Durum**: Üç Atom akışı var ve `<link rel="alternate">` ile duyuruluyor (`site.blade.php:54-62`).
- **Öneri**: Search Console'a ek sitemap olarak göndermek isteğe bağlı. Kod değişikliği gerekmiyor.

### 6.8 Yapay zekâ özellikleri ve snippet kontrolleri

- **Google**: AI Overviews ve AI Mode için ek bir gereksinim yok. Sayfanın dizinde olması ve snippet'e uygun olması yetiyor. "You don't need to create new machine readable files, AI text files, or markup". Bu trafik Search Console'da "Web" türünün içinde raporlanıyor ([AI features](https://developers.google.com/search/docs/appearance/ai-features), 2025-12-10). `nosnippet` AI Overviews ve AI Mode için de geçerli. `data-nosnippet` `span`, `div` ve `section`'da çalışıyor ([robots meta](https://developers.google.com/search/docs/crawling-indexing/robots-meta-tag)). 2026-05-15 tarihli rehber: llms.txt, "chunking", yalnızca yapay zekâ için yeniden yazma ve özel schema gerekmiyor. Yapılandırılmış veriyi genel SEO için kullanmaya devam etmek iyi bir fikir ([AI optimization guide](https://developers.google.com/search/docs/fundamentals/ai-optimization-guide), 2026-07-10; [updates, 2026-06-15: llms.txt](https://developers.google.com/search/updates)). `Google-Extended` yalnızca Gemini eğitimini ve grounding'i yönetiyor, Search'e dahil olmayı ve sıralamayı etkilemiyor ([Google'ın genel tarayıcıları](https://developers.google.com/crawling/docs/crawlers-fetchers/google-common-crawlers)).
- **Bing**: `NOCACHE` etiketi yanıtlarda yalnızca URL, başlık ve snippet'in kullanılmasına izin veriyor. `NOARCHIVE` içeriği yanıtlardan ve eğitimden çıkarıyor. İkisi de arama sonuçlarından çıkarmıyor ([Bing blog, 2023-09-22](https://blogs.bing.com/webmaster/september-2023/Announcing-new-options-for-webmasters-to-control-usage-of-their-content-in-Bing-Chat)).
- **Durum ve öneri**: Hiçbiri kullanılmıyor, bu da doğru. Görünürlük isteniyorsa ekleme yapılmamalı. Ham kişisel veriler, örneğin sansürlü hedef metinleri, zaten sayfada yok. Gerekirse belirli bir blok `data-nosnippet` ile dışarıda bırakılabilir.

### 6.9 İsteğe bağlı yeni Google özellikleri (düşük öncelik)

- **Preferred sources**: Okur siteyi "tercih edilen kaynak" seçebiliyor. Özellik Top Stories, AI Mode ve AI Overviews'u etkiliyor. Alt alan adları uygun, alt dizinler değil. Buton ya da `https://www.google.com/preferences/source?q=<domain>` linki isteğe bağlı ([preferred sources](https://developers.google.com/search/docs/appearance/preferred-sources), 2026-09-18). Haber odaklı bir özellik, bir kişisel blog için etkisi muhtemelen küçük.
- **Search profile badge**: `profile.google.com/@handle` profiline giden bir rozet. Belirtilen tek fayda Discover'da görünme olasılığı. Sıralama etkisi yazmıyor ([search profiles](https://developers.google.com/search/docs/appearance/search-profiles), 2026-09-16).

---

## Folklor / kanıtsız

Yaygın ama resmi belgelerle desteklenmeyen ya da belgelerce çürütülen iddialar:

- **"llms.txt / AI için Markdown dosyası gerekir."** Google: gerekmiyor, Search bunları kullanmıyor ([AI optimization guide](https://developers.google.com/search/docs/fundamentals/ai-optimization-guide); [AI features](https://developers.google.com/search/docs/appearance/ai-features)).
- **"E-E-A-T bir sıralama faktörüdür."** Google: "No, it's not" ([SEO starter guide](https://developers.google.com/search/docs/fundamentals/seo-starter-guide), 2025-12-10); "isn't a specific ranking factor" ([helpful content](https://developers.google.com/search/docs/fundamentals/creating-helpful-content)).
- **"Lighthouse 100 olmazsa sıralama düşer" / "CWV büyük bir sıralama faktörüdür."** Google tek bir sinyal olmadığını ve alakanın önce geldiğini söylüyor ([page experience](https://developers.google.com/search/docs/appearance/page-experience)). Lighthouse lab verisi, Search Console ise CrUX alan verisini gösteriyor. Lighthouse skorunun sıralamada kullanıldığına dair bir belge yok.
- **"Yapılandırılmış veri sıralamayı yükseltir."** Belgelerde böyle bir ifade yok. Belgelenen etki rich result'a uygunluk ve sayfanın anlaşılması ([intro](https://developers.google.com/search/docs/appearance/structured-data/intro-structured-data)).
- **"OG etiketleri SEO sıralama sinyalidir."** Belgelenmemiş. Belgelenen kullanım title link, site adı ve küçük resim kaynağı olmaları (§3).
- **"`meta keywords` işe yarar."** "Google Search doesn't use the keywords meta tag" ([starter guide](https://developers.google.com/search/docs/fundamentals/seo-starter-guide)).
- **"`rel=next/prev` gerekir."** "Google no longer uses these tags" ([pagination](https://developers.google.com/search/docs/specialty/ecommerce/pagination-and-incremental-page-loading)).
- **"`priority`/`changefreq` tarama sıklığını belirler."** Google da Bing de bunları yok sayıyor ([Google](https://developers.google.com/search/docs/crawling-indexing/sitemaps/build-sitemap), [Bing](https://blogs.bing.com/webmaster/2025/7/Keeping-Content-Discoverable-with-Sitemaps-in-AI-Powered-Search/)).
- **"`lang` özelliği ya da hreflang Google'a dili söyler."** Google `lang`'ı kullanmıyor. hreflang yalnızca alternatif dil sürümleri için ([multi-regional](https://developers.google.com/search/docs/specialty/international/managing-multi-regional-sites)).
- **"Minimum kelime sayısı vardır."** "no magical word count target" ([starter guide](https://developers.google.com/search/docs/fundamentals/seo-starter-guide)).
- **"Duplicate content cezası."** Manuel işlem sebebi değil, yalnızca verimsiz ([starter guide](https://developers.google.com/search/docs/fundamentals/seo-starter-guide)).
- **"URL'de anahtar kelime şart; numaralı URL (`/ogrendiklerim/10`) kötüdür."** Google anlamlı URL'leri kullanıcı açısından öneriyor, ama URL'deki kelimelerin breadcrumb dışında sıralamaya "hardly any effect" ettiğini söylüyor ([starter guide](https://developers.google.com/search/docs/fundamentals/seo-starter-guide)). Not adreslerini değiştirmek gerekmiyor, değiştirmek yönlendirme bedeli getirir (RAFIM.md:20).
- **"Kendi yazdığın yorumlara yıldız işareti self-serving sayılır."** Self-serving kuralı yalnızca LocalBusiness/Organization için ([review snippet](https://developers.google.com/search/docs/appearance/structured-data/review-snippet)).
- **"robots.txt sayfayı Google'dan çıkarır."** Çıkarmaz ([robots intro](https://developers.google.com/search/docs/crawling-indexing/robots/intro)).
- **"Request indexing sıralamayı hızlandırır / garanti eder."** Garanti etmiyor ve kotası var ([URL inspection](https://support.google.com/webmasters/answer/9012289)).
- **"IndexNow Google'da da çalışır."** IndexNow'ın katılımcı listesinde Google yok. Google belgelerinde de geçmiyor ([IndexNow FAQ](https://www.indexnow.org/faq)).
- **"`twitter:card` olmadan kart hiç çıkmaz."** X belgesi og:type, og:title ve og:description varsa "a summary card may be rendered" diyor ([X markup, arşiv](http://web.archive.org/web/20260201012312/https://developer.x.com/en/docs/x-for-websites/cards/overview/markup)).

## Kaynaklar

Google Search Central / Search Console Yardım
- https://developers.google.com/search/docs/appearance/core-web-vitals (2025-12-10)
- https://developers.google.com/search/docs/appearance/page-experience (2026-09-22)
- https://support.google.com/webmasters/answer/9205520 (Core Web Vitals raporu)
- https://developers.google.com/search/docs/crawling-indexing/links-crawlable (2025-12-10)
- https://developers.google.com/search/docs/specialty/ecommerce/pagination-and-incremental-page-loading (2025-12-10)
- https://developers.google.com/search/docs/fundamentals/seo-starter-guide (2025-12-10)
- https://developers.google.com/search/docs/appearance/title-link (2025-12-10)
- https://developers.google.com/search/docs/appearance/snippet (2026-04-20)
- https://developers.google.com/search/docs/appearance/google-images (2026-03-02)
- https://developers.google.com/search/docs/appearance/google-discover (2026-03-09)
- https://developers.google.com/search/docs/appearance/site-names (2025-12-10)
- https://developers.google.com/search/docs/appearance/favicon-in-search (2026-08-28)
- https://developers.google.com/search/docs/appearance/publication-dates
- https://developers.google.com/search/docs/appearance/structured-data/intro-structured-data (2025-12-10)
- https://developers.google.com/search/docs/appearance/structured-data/sd-policies (2026-07-10)
- https://developers.google.com/search/docs/appearance/structured-data/search-gallery (2026-06-15)
- https://developers.google.com/search/docs/appearance/structured-data/review-snippet (2026-09-08)
- https://developers.google.com/search/docs/appearance/structured-data/article (2026-09-08)
- https://developers.google.com/search/docs/appearance/structured-data/profile-page (2026-09-08)
- https://developers.google.com/search/docs/appearance/structured-data/movie (2026-09-08)
- https://developers.google.com/search/docs/appearance/structured-data/book (2025-12-10)
- https://developers.google.com/search/docs/appearance/structured-data/software-app (2026-09-08)
- https://developers.google.com/search/docs/appearance/structured-data/discussion-forum (2026-09-08)
- https://developers.google.com/search/blog/2025/06/simplifying-search-results
- https://developers.google.com/search/blog/2025/11/update-on-our-efforts
- https://developers.google.com/search/updates (2026-10-16 itibarıyla okundu)
- https://developers.google.com/search/docs/crawling-indexing/sitemaps/build-sitemap (2026-07-08)
- https://developers.google.com/search/docs/crawling-indexing/consolidate-duplicate-urls (2026-07-10)
- https://developers.google.com/search/docs/crawling-indexing/301-redirects (2026-04-14)
- https://developers.google.com/search/docs/crawling-indexing/robots/intro (2025-12-10)
- https://developers.google.com/search/docs/crawling-indexing/robots-meta-tag (2026-03-24)
- https://developers.google.com/search/docs/specialty/international/managing-multi-regional-sites (2025-12-10)
- https://developers.google.com/search/docs/fundamentals/creating-helpful-content (2026-10-05)
- https://developers.google.com/search/docs/appearance/ai-features (2025-12-10)
- https://developers.google.com/search/docs/fundamentals/ai-optimization-guide (2026-07-10)
- https://developers.google.com/search/blog/2026/05/a-new-resource-for-optimizing
- https://developers.google.com/crawling/docs/crawlers-fetchers/google-common-crawlers
- https://developers.google.com/search/docs/appearance/preferred-sources (2026-09-18)
- https://developers.google.com/search/docs/appearance/search-profiles (2026-09-16)
- https://support.google.com/webmasters/answer/7440203 (Page indexing raporu)
- https://support.google.com/webmasters/answer/9012289 (URL Inspection)
- https://support.google.com/webmasters/answer/34592 (property ekleme)
- https://search.google.com/test/rich-results
- https://pagespeed.web.dev/

web.dev / Chrome
- https://web.dev/articles/vitals (2024-10-31)
- https://web.dev/articles/lab-and-field-data-differences (2022-07-18)
- https://web.dev/articles/optimize-lcp (2025-03-31)
- https://web.dev/articles/browser-level-image-lazy-loading (2024-08-13)
- https://web.dev/articles/font-best-practices (2022-10-04)
- https://developer.chrome.com/docs/crux/methodology (2024-06-20)

schema.org / Open Graph / X
- https://schema.org/TVSeries
- https://ogp.me/
- https://developer.x.com/en/docs/x-for-websites/cards/overview/markup (artık docs.x.com/overview'a yönleniyor)
- http://web.archive.org/web/20260201012312/https://developer.x.com/en/docs/x-for-websites/cards/overview/markup (2026-02-01 arşiv kopyası)

Bing / IndexNow
- https://blogs.bing.com/webmaster/2025/7/Keeping-Content-Discoverable-with-Sitemaps-in-AI-Powered-Search/ (2025-07-31)
- https://blogs.bing.com/webmaster/september-2019/Import-sites-from-Search-Console-to-Bing-Webmaster-Tools
- https://blogs.bing.com/webmaster/september-2023/Announcing-new-options-for-webmasters-to-control-usage-of-their-content-in-Bing-Chat (2023-09-22)
- https://www.bing.com/webmasters/help/webmaster-guidelines-30fba23a (JavaScript ile yükleniyor, metni okunamadı)
- https://www.bing.com/indexnow/getstarted
- https://www.indexnow.org/documentation
- https://www.indexnow.org/faq
