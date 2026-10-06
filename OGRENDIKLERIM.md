# Öğrendiklerim Planı: kadir.gulec.tr

Bu dosya, "Öğrendiklerim" bölümünün (küçük notlar) kararlarını, adımlarını ve uygulama notlarını tutar. Tasarım kararları `TASARIM.md`, admin ve üyelik kararları `ADMIN.md` içinde. Yeni bir oturumda önce bu dosyayı oku, sonra işaretlenmemiş ilk adımdan devam et.

> Kararlar 2026-10-06 tarihli soru-cevap oturumunda alındı. Bütün adımlar `ogrendiklerim` dalında, her adım ayrı commit. Görsel adımlarda (1 ve 4) Kadir'in onayı beklenir, diğerleri onaysız ilerler.

---

## 1. Fikir

Bazı günler küçük bir şey öğreniyorum. Bir yazı kadar uzun değil, iki üç cümle. Bunlar post-it gibi bir panoya yapıştırılır, üstünde tarih ve etiket olur. Sayfa başlığı: **"Küçük notlar, büyük birikim"**.

Örnek notlar:

- Yüzerken başı biraz daha aşağıda tutmak kalçayı yukarı kaldırıyor; su direnci gözle görülür azalıyor.
- Laravel'de `Model::preventLazyLoading()` geliştirme ortamında N+1 sorgularını anında yakalıyor.
- Almanca "Feierabend" kelimesinin Türkçede tam karşılığı yok; en yakını "mesai sonrası huzuru".

## 2. Navigasyon

- **Yeni sekme:** "Öğrendiklerim", `/ogrendiklerim`, bölüm rengi **petrol / deniz yeşili** (yaklaşık `#2F7F7A`; gece defterinde parlak turkuaz). Kesin ton prototipte, iki temada ve AA kontrolüyle belirlenir.
- **Sekme sırası:** Ana Sayfa · Yazılar · Öğrendiklerim · İzlediklerim · Hedefler · Projeler · Hakkımda.
- **Masaüstü:** Yedi sekmenin hepsi yan sekmelerde durur.
- **Mobil alt çubuk:** 5 sütun: Yazılar · Öğrendiklerim · İzlediklerim · Hedefler · Projeler. "Ana Sayfa"ya köşedeki "kg" damgasıyla, "Hakkımda"ya footer'daki linkle gidilir (`Section::isInMobileBar()`). *(2026-10-06: 6 sütunda "Öğrendiklerim" 360 ve 375 px'te "Öğrendikl…" diye kesildi; yedek plan uygulandı. Etiketler `tracking-tight`, 360 px'te sığıyor.)*
- **Mobilde küçülen damga (sadece `lg` altı):** Sayfa kayınca tepedeki "kgülec" imzası ekrandan çıkar çıkmaz (`IntersectionObserver`) sol üst köşede küçük, sabit bir "kg" damgası belirir; imza geri gelince kaybolur. Ana sayfaya link verir. Şerit yok, dairenin içi kâğıt renginde dolu (açık: krem, gece: kömür), hafif gölge ve eğim; "sayfanın üstüne yapışmış" gibi durur. Lamba yukarıda kalır, sabit değildir. `prefers-reduced-motion` açıksa animasyonsuz. Sayfa geçişlerinde (view transition) kendi `view-transition-name`'i olur (alt çubukta yaşanan hata tekrarlanmasın).

## 3. Not

- **Alanlar:** Başlık **yok**. `body` (Markdown) + `body_html`, `tag_id` (zorunlu, tam olarak bir etiket), `published_at` (`HasPublication`: boş taslak, gelecekte zamanlanmış, geçmişte yayında). Post-it'te görünen tarih `published_at`'tir; geçmişe dönük not için tarih geri alınır. Formda varsayılan "şimdi".
- **Biçim:** Yazılardaki Markdown converter'ı (yeni converter yok). Editördeki ⓘ kılavuzu sadece satır içi biçimleri önerir: kalın, italik, `satır içi kod`, link, `==fosforlu kalem==`. Gerekirse notlarda kenar notları converter'ın `$sidenotes` parametresiyle kapatılır.
- **Uzunluk:** Yumuşak sınır. Editörde sayaç (ham metin): 250'den sonra turuncu, 500'den sonra kırmızı. Kaydetmeye hiç engel olmaz.
- **Adres:** `/ogrendiklerim/{id}`. Numara kalıcıdır, slug ve yönlendirme yok.
- **Etiketler:** Yazılarla **ortak** `tags` tablosu, notta `notes.tag_id` (`belongsTo`). Notlarda kullanılan bir etiket silinemez, sadece birleştirilebilir (`mergeInto()` notları da taşır). İleride çoklu etiket istenirse pivota çevrilir.
- **Yorum yok.**

## 4. Ziyaretçi sayfaları

- **Pano (`/ogrendiklerim`):** Başlık "Küçük notlar, büyük birikim". Üstte etiket filtresi (`?etiket=slug`, yazılardaki gibi, sayılarla). **Masonry** düzen (CSS `columns`; sıra sütun sütun akar, kronoloji ikinci planda). Mobilde 1, tablette 2, masaüstünde 3 sütun. Klasik sayfalama, sayfa başına yaklaşık 30 not, `withQueryString()`. Laravel'in sayfalama görünümü defter tasarımına uyarlanır.
- **Post-it:**
  - Renk: 4–5 pastel post-it renginden biri, not numarasından türetilir (deterministik, saklanmaz). Her rengin açık ve gece versiyonu var, metin AA'yı geçer.
  - Hafif eğim (±2°, numaradan), hover'da düzleşir (`sticky-note` gibi).
  - Post-it'i **etiketin washi tape'i** tutar: bant etiketin renginde, üstünde etiketin adı yazar, tıklanınca filtreler.
  - Metin Nunito Sans, tarih JetBrains Mono, küçük süslemeler (ör. köşede `#42`) Caveat.
  - Kartın kendisi not sayfasına link verir.
- **Not sayfası (`/ogrendiklerim/{id}`):** Büyük tek post-it, önceki/sonraki not (`published_at` sırası), aynı etiketli en fazla 3 not ve aynı etiketli en fazla 2 yazı ("bu konuda yazdıklarım").
- **Yazı sayfası:** İlgili yazılardan önce, yazının etiketlerinden en fazla 3 post-it ("bu konuda küçük notlar"). Hiç yoksa şerit görünmez.
- **Ana sayfa:** En son not, tek bir post-it kartı, "Tümü →" ile. *(2026-10-06: Tek kart sayısı 5 olunca ızgara yerine iki serbest sütun: CSS `columns`, kartlar `inline-block w-full` (sütun başındaki kartın taşan bandı önceki sütuna kaymasın). HTML sırası her ekranda aynı okuma sırası: izlediğim, yazdığım, öğrendiğim, zincirler, izliyorum; proje en altta tam genişlikte. `order` ile görsel sırayı değiştirmek klavye ve ekran okuyucu sırasını bozacağı için kullanılmadı.)*
- **Taslaklar:** Diğer içerikler gibi sadece yetkiliye "TASLAK" bandıyla, ziyaretçiye 404.
- **`x-site.tag`:** Linki şu an `posts.index`'e sabit. Hedef rota parametresi alacak.

## 5. Paylaşım, RSS ve bildirimler

- **Başlık ve meta:** `<title>` notun düz metninin ilk ~60 karakteri, meta açıklama ilk ~160 karakteri.
- **OG görseli:** Mevcut `OgImage` sistemiyle (ilk istekte çizilir, `public/og/`), notun post-it'i: rengi, bandı ve metni.
- **RSS:** Ayrı Atom akışı `/ogrendiklerim/rss`. Girdi başlığı ilk ~60 karakter, etiket `<category>`, gövde tam metin. Layout'ta `<link rel="alternate">`.
- **Sitemap:** Yayındaki not sayfaları eklenir.
- **Bildirim:** Ayrı abonelik `users.notify_new_notes` (pano sayfasında düğme, Hesabım → Bildirimler'de anahtar). Not bildirimleri **anında gitmez**: üyenin tercihi "hemen" olsa bile sadece günlük/haftalık özete girer. Zamanlanmış notlar `notifications:announce` ile duyurulur.

## 6. Admin

- **İzin:** `Permission::ManageNotes` (yeni).
- **Kenar çubuğu:** İçerik → Öğrendiklerim.
- **Ekranlar:** `/admin/ogrendiklerim` (liste: arama, etiket filtresi, durum), `/admin/ogrendiklerim/yeni`, `/admin/ogrendiklerim/{note}` (Markdown editör + sayaç, etiket combobox'ı (yoksa oluşturur), `published_at`).
- **Hızlı not:** Admin panosunun tepesinde bir kart: textarea, etiket seçici, "Yapıştır" düğmesi. Not hemen yayına girer, kart temizlenir.
- **PWA kısayolu:** Manifest'e `shortcuts` girdisi "Yeni not" → `/admin/ogrendiklerim/yeni`.
- **Etiket ekranı:** `/admin/yazilar/etiketler` → `/admin/etiketler` (İçerik altında). `ManagePosts` ya da `ManageNotes` yeterli. Her etiketin yazı ve not sayısı görünür. Eski adres yeni adrese yönlenir.
- **Yedekleme:** `notes` tablosu manifest'teki içerik sayılarına eklenir.

## 7. Adımlar

- [x] **Adım 1: Mobil navigasyon** (`Section::Notes`, petrol rengi, yan sekmeler, mobil çubuktan Ana Sayfa'nın çıkması, küçülen "kg" damgası, view transition; şimdilik `/ogrendiklerim` boş bir pano sayfası). *Kadir'in onayı beklenir.*
- [x] **Adım 2: Model ve etiketler** (`notes` tablosu, `Note` modeli, factory, `Tag::notes()`, birleştirme ve silme kuralları, etiket ekranının taşınması, `ManageNotes` izni, yedek sayımı, demo notlar).
- [x] **Adım 3: Admin** (liste, form, sayaç, ⓘ kılavuz, Hızlı not kartı, PWA kısayolu).
- [x] **Adım 4: Ziyaretçi sayfaları** (post-it bileşeni ve paleti, masonry pano, etiket filtresi, sayfalama görünümü, not sayfası, yazı sayfasındaki şerit, ana sayfa kartı). *Kadir'in onayı beklenir.*
- [x] **Adım 5: Paylaşım ve bildirimler** (title/meta, OG görseli, RSS, sitemap, `notify_new_notes`, sadece özete giren bildirimler).

## 8. Uygulama notları

*(Her adımda buraya eklenir.)*

- **Navigasyon (1):** `Section::Notes` (`notes`, "Öğrendiklerim", `notes.index`). Renk token'ları `--color-notes` / `-ink` / `-on` (açık: `#2a7671` / `#236660` / krem; gece: `#5fd4c8` / `#7ee0d6` / kömür; kâğıtta ve `paper-deep`'te AA), admin'de `--color-section-notes`, OG paletinde `notes`. Sekme ikonu köşesi kıvrık bir post-it. Damga `x-site.home-stamp` (layout'ta, `lg:hidden`); `site.js` → `initHomeStamp()` imzayı (`[data-signature]`) gözler, `.is-shown` sınıfı `stamp-in` animasyonunu oynatır, gizliyken `visibility: hidden` (sekme sırasından çıkar). View transition'da kendi katmanı (`home-stamp`), lamba animasyonu sırasında kapalı. Footer'da "hakkımda" linki. `/ogrendiklerim` şimdilik boş bir pano (`NotesController::index`).
- **Model ve etiketler (2):** `notes` tablosu (`tag_id` → `tags`, `restrictOnDelete`; `body`, `body_html`, `published_at`). `Note` (`HasPublication`, `RendersMarkdown`, `tag()`, `useTagNamed()`: adı verilen etiketi bulur ya da oluşturur, `publicPath()`), morph map'te `note`. `Tag::notes()`, `Tag::isDeletable()` (notu olan etiket silinmez), `mergeInto()` notları da taşır. Etiket ekranı `/admin/etiketler` (`admin.tags.index` adı aynı kaldı; eski `/admin/yazilar/etiketler` 301), `manage-tags` gate'i: `posts.manage` ya da `notes.manage`. Ekranda yazı ve not sayısı; notu olan etiketin menüsünde "Sil" yok. Kenar çubuğunda İçerik → Öğrendiklerim (rota 3. adımda gelir, o zamana kadar gizli) ve Etiketler. `Permission::ManageNotes` (`notes.manage`, İçerik grubu; deploy'da `permissions:sync`). Yedek manifest'i not sayısını da tutar. Örnek notlar `database/seeders/data/demo-notes.php` (13 not, biri taslak; etiketler yazılarla ortak).
- **Admin (3):** `/admin/ogrendiklerim` (arama, etiket ve durum filtresi), `/admin/ogrendiklerim/yeni`, `/admin/ogrendiklerim/{note}` (`notes.manage`). Form mantığı `App\Livewire\Forms\NoteForm` (`startNew()`: yeni not "şimdi" tarihiyle gelir; `SOFT_LIMIT` 250, `HARD_HINT` 500) + `App\Actions\Notes\SaveNote`. Etiket alanı tek bir input + `<datalist>` (tarayıcının kendi önerileri; yazılan etiket yoksa oluşur). `x-admin.markdown` bileşenine `inline` modu: kılavuz ve araç çubuğu sadece satır içi biçimleri sunar (diğerleri çalışır, önerilmez). `x-admin.char-counter`: `$wire.$get()` ile canlı uzunluk, 250'den sonra turuncu "not uzuyor", 500'den sonra kırmızı "bu artık bir yazı olabilir"; kaydı hiç engellemez (sunucuda sadece 5000 karakterlik teknik sınır). Panoda "Hızlı not" kartı (`addQuickNote`, `notes.manage` ister; not hemen yayına girer, kart boşalır) ve taslak sayılarında Öğrendiklerim. `x-admin.badge color="notes"`. Manifest kısayolları: herkese Öğrendiklerim, Hedefler, Yazılar, İzlediklerim; en başta günlük işler: `goals.manage` olana "Zincirler" (`/admin`, panodaki "Bugün" kartı), `notes.manage` olana "Yeni not" *(2026-10-06'da eklendi)*. Bunun için manifest `crossorigin="use-credentials"` ile istenir ve `Cache-Control: private, no-cache` döner (kısayol listesi kişiye göre değişir; telefon kurulumda alır).
- **Ziyaretçi sayfaları (4):** `App\Support\Content\NoteContent` (`board()`: sayfa başına 30, sayfa parametresi `?sayfa=`, `withQueryString()`; `tags()`, `latest()`, `find()` taslağı sadece `notes.manage`'e, `neighbours()`, `sameTag()`, `forPost()`), `PostContent::withTag()`. Renk `NoteContent::COLORS[id % 5]` (sarı, pembe, mavi, yeşil, turuncu), eğim `((id × 7) mod 9 − 4) / 2` derece. `x-site.post-it` (`size` md/lg, `linked`: kartın tamamı not sayfasına gider, metindeki linkler ve etiket bandı üstte kalır), palet `.post-it[data-color]` (site.css, iki temada AA). Post-it metninde `overflow-wrap: anywhere`: boşluksuz uzun kod ya da link kartın dışına taşmaz, gerekirse ortasından bölünür. Etiket bandının rengi `Tag::tapeColor()` (`x-site.tag` ile ortak; `x-site.tag`'e `route` parametresi). `x-site.pagination` (defter tarzı "← daha yeni · sayfa 2 / 5 · daha eski →"). Pano CSS `columns` ile masonry. Not sayfası `/ogrendiklerim/{id}` (`whereNumber`), `<title>` notun ilk ~60 karakteri. Yazı sayfasında ilgili yazılardan önce "Bu konuda küçük notlar", ana sayfada "son öğrendiğim" kartı.
- **Paylaşım ve bildirimler (5):** OG görseli `og/note/{id}.png` (`OgImageController::note()`, `OgImage::postIt()`: defter kartının içinde notun post-it'i, rengi, bantta etiket, metin en fazla 5 satır, köşede numara; taslakta 404). Not sayfası `og-type="article"`. Atom akışı `/ogrendiklerim/rss` (`FeedController::notes()`, `feeds/notes.blade.php`; başlık düz metnin ilk ~60 karakteri, etiket `<category>`, gövde `toFeedHtml()`), layout'ta ikinci `<link rel="alternate">`. Sitemap'te yayındaki notlar. Bildirim: `notes.announced_at`, `users.notify_new_notes`, `notification_items.digest_only` (migration'da mevcut yayındaki notlar duyurulmuş sayılır). `notifications:announce` yayına giren notları `Announcements::note()` ile abonelere "Yeni not: #etiket" olarak `digestOnly: true` yazar. `notifications:send instant` bu öğeleri atlar; `daily` çalışması günlük üyelerin yanında "hemen" üyelerin özet-only öğelerini de gönderir (haftalık üyeler haftalık özetle alır). Panoda `<livewire:site.note-subscription>` düğmesi, Hesabım → Bildirimler'de "Yeni öğrendiklerimi özetle gönder", veri indirmede `notify_new_notes`.
- **Tarayıcı testleri:** `tests/Browser/NotesTest.php` (Pest browser + Playwright; yerelde `npm install` ile `playwright` paketi gerekir). Kaydırınca beliren "kg" damgası, 360 px'te beş sekme adının sığması, post-it'te bağlantı katmanları (kart → not, bant → etiket filtresi, metindeki link → kendi adresi), uzun kodun karttan taşmaması, editördeki canlı sayaç. Not: Pest'in `wait()` çağrısı sayfanın kaydırma konumunu sıfırlıyor; kaydırıp ölçmek tek bir `script()` içinde yapılır (`stampVisibilityAt()`).
