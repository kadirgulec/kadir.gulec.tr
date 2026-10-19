# SEO Planı (2. tur): kadir.gulec.tr

Bu dosya, 2026-10-12'deki ilk SEO turundan (JSON-LD, canonical, sitemap, HSTS) sonraki ikinci turun kararlarını, adımlarını ve Kadir'in kendi yapacaklarını tutar. Kaynaklı araştırma `SEO-ARASTIRMA.md` içinde; bu dosyadaki her madde oradaki bir bulguya dayanıyor. Yeni bir oturumda önce bu dosyayı oku, sonra işaretlenmemiş ilk adımdan devam et.

> Kararlar 2026-10-19 tarihli soru-cevap oturumunda alındı. Bütün adımlar `seo-2` dalında, her adım ayrı commit. Kadir adımların hepsinin aralıksız yapılmasını onayladı; sonunda fark ve görünür değişiklikler gösterilir. Aylık değerlendirmenin ilk taslağı 1 Kasım'da geldiği için dal ondan önce merge edilmeli.

---

## 1. Kapsam

Araştırmanın özetindeki 13 madde dört gruba ayrıldı:

- **A. Karar gerektirmeyen düzeltmeler** (bu turda): giriş sayfaları, boş meta description, LCP görseli, `max-image-preview`, PNG favicon, küçük düzeltmeler.
- **B. Karar gerektiren değişiklikler** (bu turda): site adı, aylık değerlendirme, notların JSON-LD'si, yazar bilgisi.
- **C. Kadir'in kendisi** (paralel, bkz. bölüm 4): Search Console, Bing, Rich Results Test.
- **D. Ertelendi**: IndexNow (ayrı, küçük bir özellik; yalnızca Bing/Yandex için) ve `font-display: block` kararının yeniden tartılması (önce Search Console'da alan verisi birikmeli).

## 2. Kararlar

### Site adı

Her yerde **Kadir Gülec**. `WebSite.name` ve `og:site_name` "Kadir Gülec" olur, `kadir.gulec.tr` `alternateName`'e geçer. Title ve manifest zaten "Kadir Gülec" diyor.

### Giriş sayfaları ve robots.txt

`/login`, `/register` ve `/forgot-password` herkese açık linkleniyor (footer, giriş sayfası). Bu üçü `Disallow`'dan çıkar ve bütün auth layout'una `noindex, nofollow` eklenir; Google ancak tarayabildiği sayfadaki `noindex`'i görür. Token ya da oturum isteyen (`/reset-password`, `/two-factor-challenge`), girişe yönlenen (`/admin`, `/hesap`, `/user/`) ve yalnızca e-postadan gelinen (`/bildirimler/`, `/takip/`) yollar engelli kalır.

### Boş meta description

Kitap ve film/dizi sayfalarında sıra: `meta_description` → özet (`overview`) → yorumun spoiler'sız alıntısı → üretilen bir cümle (kitapta yazar, filmde yönetmen ya da yaratıcıyla). Layout da `??` yerine `filled()` ile varsayılana düşer, böylece hiçbir sayfa (hedefler dahil) boş description vermez.

### LCP, görsel önizleme, favicon

- Detay sayfalarının ana görseli (afiş, kitap kapağı, proje görseli, hedef görseli) `loading="eager"` + `fetchpriority="high"`. Listeler lazy kalır.
- Dizine girebilen her sayfada `max-image-preview:large`.
- SVG'nin yanına `/icons/icon-192.png` PNG favicon linki (Google'ın favicon belgesi SVG'yi saymıyor).

### Aylık değerlendirme

- `/hedefler/aylik` 302 ile son aya yönlenmeye devam eder; yayında bir değerlendirme varsa sitemap'te yalnızca ay adresleri durur. Altı aydan sonra gerçek bir arşiv sayfası yeniden düşünülebilir.
- `h1`'in başına ay adı ekran okuyucuya özel (`sr-only`) olarak girer: "Ekim 2026 değerlendirmesi: Her ayın sonunda dürüst bir bakış." Görünüm değişmez (notlardaki sr-only `h1` ile aynı yaklaşım).

### Notlar ve değerlendirmeler için JSON-LD

İkisine de `BlogPosting` (Google'ın rich result'ı yok, amaç sayfanın tarihli ve yazarı belli bir yazı olarak anlaşılması; `DiscussionForumPosting`/`SocialMediaPosting` site sahibinin içeriği için yasak). İkisinde de `og:type="article"` ve `article:published_time`.

### Yazar

- Yazının sonundaki el yazısı "Kadir" imzası `/hakkimda`'ya link olur; altı çizilmez, yalnızca hover rengi.
- `Person`'a kısa bir `description`.
- Fotoğraf yok (Kadir şimdilik istemiyor), bu yüzden `image` ve `ProfilePage.dateModified` da yok.

### Küçük düzeltmeler

- `BlogPosting.description` `trim` edilir.
- `og:image:alt` başlığı değil, görselin içeriğini anlatır.
- `twitter:image:alt` silinir (X `og:image:alt`'a geri düşüyor).
- Dışarıda: kitap/film için `og:type=book`/`video.movie` (yalnızca sosyal önizleme) ve Article için çoklu en-boy oranlı görseller.

## 3. Adımlar

- [x] **Adım 1: robots.txt ve auth `noindex`**
- [x] **Adım 2: Meta description geri düşüşleri**
- [x] **Adım 3: LCP görseli, `max-image-preview`, PNG favicon**
- [x] **Adım 4: Site adı, `og:image:alt`, `twitter:image:alt`, `BlogPosting.description`**
- [x] **Adım 5: Notlar ve değerlendirmeler için `BlogPosting`, `og:type`, `published_time`**
- [x] **Adım 6: Aylık değerlendirme: sitemap ve sr-only `h1`**
- [ ] **Adım 7: Yazar imzası ve `Person.description`**

## 4. Kadir'in yapacakları

- [ ] **Google Search Console**: `kadir.gulec.tr` için domain property (DNS TXT kaydıyla doğrulama), `https://kadir.gulec.tr/sitemap.xml` gönderimi. İsteğe bağlı: üç Atom akışı da sitemap olarak gönderilebilir. Haftada bir Page indexing raporuna bakmak.
- [ ] **Bing Webmaster Tools**: siteyi Search Console'dan içe aktarmak.
- [ ] **Rich Results Test**: deploy'dan sonra bir dizi sayfasını (`TVSeries` yorumu) test etmek; Google'ın review snippet listesinde `TVSeries` adı geçmiyor.
- [ ] **Alan verisi**: birkaç hafta sonra Search Console'un Core Web Vitals raporuna bakmak; `font-display` kararı (D grubu) buna göre.

## 5. Uygulama notları

*(Her adımda buraya eklenir.)*

- **robots.txt ve auth (1):** `SitemapController::robots()` artık `/login`, `/register`, `/forgot-password` için `Disallow` yazmıyor. Auth layout'u site layout'una `noindex` geçiyor, yani giriş, kayıt, şifre sıfırlama, iki adımlı doğrulama ve e-posta doğrulama sayfalarının hepsi `noindex, nofollow` alıyor ve JSON-LD basmıyor (site layout'undaki mevcut dal). `/passkeys/login/options` bir JSON ucu, linklenmiyor, dokunulmadı.
- **Meta description (2):** `BookContent::metaDescription()` ve `WatchedContent::metaDescription()`: `meta_description` (kısaltılmadan) → özet → yorumun spoiler'sız alıntısı (ikisi 155 karakterde kesilir) → "Kadir'in okuduklarından bir kitap: Başlık, Yazar." / "Kadir'in izlediklerinden bir film: Başlık (yıl), yönetmen." Yorum alıntısı yalnızca yorum herkese açıksa (ya da Kadir önizliyorsa) girer. Site layout'u `??` yerine `filled()` kullanıyor; boş string gelirse site açıklamasına düşer (ör. `why`'ı boş hedef). Değerlendirmede boş `summary` da ay cümlesine düşüyor. Cümlelerin ifadesi Kadir'le gözden geçirilecek.
- **LCP, önizleme, favicon (3):** `poster`, `book-cover`, `browser-frame` ve `project-shot` bileşenlerinde `priority` prop'u: açıkken `fetchpriority="high"` (tarayıcının varsayılanı zaten `eager`), kapalıyken `loading="lazy"`. Açık olduğu yerler: film/dizi ve kitap detayındaki afiş/kapak, proje detayındaki ekran görüntüsü, Projeler sayfasındaki öne çıkan proje ve hedef detayındaki görsel. Listeler ve ana sayfa lazy kaldı. `max-image-preview:large` site layout'unda JSON-LD ile aynı dalda, yani yalnızca dizine girebilen sayfalarda. PNG favicon (`/icons/icon-192.png`) SVG'den önce listeleniyor; SVG'yi destekleyen tarayıcılar sondakini seçiyor. Admin ve plain layout'larına dokunulmadı (`noindex`).
- **Site adı ve görsel metni (4):** `WebSite.name` = `config('about.person.name')` ("Kadir Gülec"), `alternateName` = `kadir.gulec.tr`; `og:site_name` "Kadir Gülec". OG kartında alt satırda alan adı damgası duruyor, ona dokunulmadı. `og:image:alt`: "Defter sayfasına yazılmış “Başlık” başlığı ve kadir.gulec.tr damgası" (not kartı post-it olsa da genel ifade yeterli). `twitter:image:alt` silindi. Yazı açıklaması `PostContent`'te `trim` ediliyor, böylece meta etiketi ve JSON-LD aynı metni taşıyor.
- **Not ve değerlendirme JSON-LD (5):** `StructuredData::notePosting()` (`@id` `…#not`; `headline` sayfanın `<title>`'ı olan ilk kelimeler, `description` notun metni, `keywords` etiket, `image` OG kartı; notun `updated_at`'i dizide olmadığı için `dateModified` yok) ve `StructuredData::reviewPosting()` (`@id` `…#degerlendirme`; `headline` "Eylül 2026 değerlendirmesi", `description` özet, `datePublished`/`dateModified`). `ReviewContent::toArray()` artık `url`, `publishedAt`, `updatedAt` veriyor; `updatedAt` yazılardaki gibi yayından önceye düşmüyor. Not sayfasında `article:published_time`, değerlendirmede `og:type=article` ve `article:published_time`/`modified_time`. Değerlendirme yokken boş sayfa `website` olarak kalıyor ve düğüm basmıyor.
- **Değerlendirme sitemap ve `h1` (6):** Sitemap'te yalnızca yayındaki ayların adresleri; `/hedefler/aylik` hiç listelenmiyor (ilk değerlendirmeden önce zaten listelenmiyordu, sonra 302 olacağı için de). Hedefler bölümünün `lastmod`'u ayları kapsamaya devam ediyor. `h1`'in başında `sr-only` "Eylül 2026 değerlendirmesi: "; değerlendirme yokken boş sayfanın başlığı değişmedi.
