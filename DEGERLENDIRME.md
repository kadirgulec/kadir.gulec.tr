# Aylık Değerlendirme Planı: kadir.gulec.tr

Bu dosya, "Her ayın sonunda dürüst bir bakış" sayfasının kararlarını, adımlarını ve uygulama notlarını tutar. Hedef sistemi kararları `ADMIN.md` (Hedefler) ve `TASARIM.md` (Hedefler) içinde. Yeni bir oturumda önce bu dosyayı oku, sonra işaretlenmemiş ilk adımdan devam et.

> Kararlar 2026-10-09 tarihli soru-cevap oturumunda alındı. Bütün adımlar `degerlendirme` dalında, her adım ayrı commit. Görsel adımda (3) Kadir'in onayı beklenir, diğerleri onaysız ilerler.

---

## 1. Fikir

Her ayın sonunda o aya dürüstçe bakan bir sayfa: ne iyi gitti, nerede zorlandım, gelecek ay neyi deneyeceğim. Rakamlar sitedeki kayıtlardan (zincirler, hedefler, yayınlar, ziyaretler) otomatik gelir, yorumu Kadir yazar.

Başlık: **"Her ayın sonunda dürüst bir bakış."** Alt metin: "Ne iyi gitti, nerede zorlandım, gelecek ay neyi değiştireceğim. Rakamlar sitedeki kayıtlardan otomatik geliyor."

## 2. Otomasyon: taslak + öneriler

- **Ayın 1'inde 00:15'te** (`Europe/Berlin`) `reviews:create` komutu geçen ayın değerlendirmesini **taslak** olarak oluşturur. Varsa dokunmaz. Rakamlar o anda hesaplanıp **dondurulur** (snapshot).
- **Neden dondurma:** Zincir günleri sonradan düzeltilebilir, ziyaret kayıtları 13 ay sonra silinir (`PageView::KEEP_MONTHS`). Değerlendirme yazıldığı anın fotoğrafı olarak kalmalı. Admin'de "Rakamları yeniden hesapla" düğmesi var (yayından önce düzeltme için).
- **Hatırlatma:** Taslak oluşunca Kadir'e e-posta gider ("Ekim değerlendirmesi hazır") ve admin panosunda bir kart görünür. Adres `SendContactMessage::recipient()`, zincir hatırlatmalarındaki gibi.
- **Öneriler:** Admin'de, dondurulmuş rakamlardan üretilen öneri maddeleri ayrı bir listede durur. Her önerinin yanında "İyi gidenlere ekle" / "Zorlandıklarıma ekle". Öneriler kaydedilmez, her açılışta rakamlardan yeniden üretilir; sadece eklenenler madde olur. Örnekler:
  - Zincir ayın %90'ından fazlasında tuttu: "Kitap zinciri: 31 günün 30'unda".
  - Zincirde o ay yeni rekor seri: "Kitap zincirinde yeni rekor: 23 gün".
  - Sayısal hedef "önde": "20 kitap hedefinde önde (14 / 20)".
  - Ayın en çok okunan yazısı: "'Pest tarayıcı testleri…' ayın en çok okunan yazısı oldu".
  - Zincir ayın yarısından azında tuttu: "Laracasts zinciri: 30 günün 9'unda".
  - Sayısal hedef "biraz geride", o ay hiç yazı yayınlanmadı.
  - "Deneyeceğim" maddesi için öneri yok; o kişisel.
- **Yayın elle:** Kadir özeti, puanı ve maddeleri yazar, sonra yayınlar (`HasPublication`: boş taslak, ileri tarih zamanlanmış). İlk değerlendirme **Ekim 2026** (zincirler 5 Ekim'de başladı), 1 Kasım'da oluşur. Admin'de istenen bir ay için elle oluşturma da var (test ve geriye dönük).

## 3. Değerlendirmenin içeriği

- **Ay:** `month` (ayın ilk günü, benzersiz). Adres `/hedefler/aylik/2026-10`.
- **Özet cümlesi (isteğe bağlı):** Tek satır, ör. "Spor rutini oturdu, yazı tarafı yine aksadı." Boşsa görünmez.
- **Ayın puanı (isteğe bağlı):** 1–10, Kadir verir. Boşsa görünmez.
- **Maddeler:** Üç tür: **İyi giden (+)**, **Zorlandığım (–)**, **[Sonraki ay]'da deneyeceğim (→)**. Her biri kısa düz metin (satır içi Markdown: kalın, eğik, link). Sıralanabilir. Boş tür sayfada görünmez.
- **Geçen ayın denemeleri:** Eylül değerlendirmesindeki "Ekim'de deneyeceğim" maddeleri, Ekim değerlendirmesinde "Ekim'de denediklerim" olarak tekrar çıkar. Kadir her birini **yaptım / yapmadım** diye işaretler (`outcome`). Yapılanlar işaretli kutuyla, yapılmayanlar hedeflerin geçmiş yıllar arşivindeki gibi **üstü karalanmış** ve "olmadı" notuyla görünür. İşaretlenmemiş olan nötr kalır.
- **Rakam kutuları:** Hepsi snapshot'ta. Admin'de kutu kutu gizlenebilir (ör. çok fazla zincir varsa).
  - **Zincirler:** Her açık ya da sansürlü zincir için o ay yapılan gün sayısı ("27 gün · Kitap"). Haftalık/aylık zincirde tutan dönem sayısı da ("4 / 4 hafta"). Gizli zincirler hiç girmez.
  - **Yayınlananlar:** O ay yayınlanan yazı, Öğrendiklerim notu, izlenen film/dizi (`viewings.watched_on`).
  - **Sayısal yıllık hedefler:** O ay eklenen ilerleme ("+2 kitap") ve ay sonundaki gidişat (`GoalPace`: önde / yolunda / biraz geride).
  - **Ziyaretçiler:** Ayın toplam sayfa görüntüleme sayısı, ziyaretçi sayısı (günlük tuzlu hash'ler günden güne bağlanamadığı için "günlük tekil ziyaretçilerin toplamı", dürüstçe böyle etiketlenir) ve en çok okunan yazı.
- **Sansür kuralları:** Snapshot'ta hedef başlığı değil `goal_id` ve rakamlar saklanır. Başlık her gösterimde **o anki** görünürlüğe göre çözülür: sansürlü hedef izinsiz kişiye `🔒 ██████` olarak görünür, sonradan gizlenen hedefin kutusu kaybolur. Mevcut `GoalCensor` ve `ADMIN.md`'deki görünürlük kuralları geçerli.

## 4. Ziyaretçi sayfası

- **Yer:** Hedefler bölümü (`Section::Goals`, aynı renk). `/hedefler/aylik` en son yayınlanan değerlendirmeye yönlenir (hiç yoksa boş durum). `/hedefler/aylik/{yyyy-mm}` değerlendirmenin kendisi. Taslak sadece yetkiliye "TASLAK" bandıyla, ziyaretçiye 404 (diğer içerikler gibi).
- **Düzen** (ekran görüntüsünden esinlenerek, ama sitenin defter tasarımıyla; koyu ekran görüntüsü birebir kopyalanmaz):
  1. Küçük üst başlık "AYLIK DEĞERLENDİRME", büyük başlık (Fraunces), yanında alt metin.
  2. Ay sekmeleri (son 4 yayınlanmış ay, `x-site.tabs`) ve sağda "Sıradaki değerlendirme: 30 Kasım" (bu ayın son günü).
  3. Ay başlığı "Ekim 2026", altında özet cümlesi, sağda büyük puan "8 /10 ayın puanı".
  4. Rakam kutuları satırı.
  5. Üç sütun: **+ İyi giden**, **– Zorlandığım**, **→ Kasım'da deneyeceğim**. Mobilde alt alta.
  6. "Ekim'de denediklerim": geçen ayın denemeleri, kutucuk ve karalama ile.
  7. Önceki / sonraki ay linkleri, en altta eski ayların arşiv listesi.
  8. Yorumlar.
- **Hedefler sayfasından link:** `/hedefler`'in üstünde küçük bir kart: "Aylık değerlendirme · Ekim 2026 · 8/10 →".
- Gece defteri (dark mode) ve `prefers-reduced-motion` diğer sayfalardaki gibi.

## 5. Yorum, bildirim, RSS, paylaşım

- **Yorumlar açık.** `PostComment` zaten her modelle çalışıyor; sadece yorum bileşeni (`⚡comments`) ve yorum moderasyon ekranı şu an yazıya bağlı. Bileşen "yorum yapılabilir model" alacak şekilde genelleşir (yazılar aynen çalışmaya devam eder, testleri korur).
- **Bildirim:** Ayrı abonelik `users.notify_monthly_reviews` (değerlendirme sayfasında düğme, Hesabım → Bildirimler'de anahtar). Yayın zamanı gelince `notifications:announce` duyurur (`announced_at`). Yazılardaki gibi üyenin tercihine göre (hemen / günlük / haftalık).
- **RSS:** Ayrı Atom akışı `/hedefler/aylik/rss`. Girdi başlığı "Ekim 2026 değerlendirmesi", gövde özet + maddeler. Layout'ta `<link rel="alternate">`. *(İstenirse yazı akışına karıştırılabilir; ayrı akış yazı okuyucularını hedef içeriğiyle doldurmaz.)*
- **OG görseli:** Mevcut `OgImage` sistemiyle, kind `aylik`: ay adı, puan ve özet cümlesi.
- **Sitemap:** Yayındaki değerlendirmeler eklenir.

## 6. Admin

- **İzin:** Mevcut `Permission::ManageGoals`.
- **Kenar çubuğu:** Hedefler altında "Aylık değerlendirme".
- **Ekranlar:** `/admin/hedefler/aylik` (liste: ay, durum, puan, "Bu ayı oluştur") ve `/admin/hedefler/aylik/{review}` (özet, puan, üç madde listesi (ekle, düzenle, sırala, sil), öneriler listesi, geçen ayın denemeleri için yaptım/yapmadım, rakam kutularını göster/gizle, "Rakamları yeniden hesapla", yayın tarihi).
- Flux yok; mevcut `x-admin.*` bileşenleri.
- **Yedekleme:** Yeni tablolar yedek manifest'indeki içerik sayılarına eklenir.

## 7. Veri modeli

- `monthly_reviews`: `id`, `month` (date, unique), `summary` (string, null), `score` (tinyint, null, 1–10), `stats` (json), `hidden_stats` (json, gizlenen kutuların anahtarları), `published_at`, `announced_at`, timestamps.
- `review_items`: `id`, `monthly_review_id`, `kind` (`good` / `hard` / `try`), `body`, `body_html`, `outcome` (null / `done` / `not_done`; sadece `try`, sonraki ayda işaretlenir), `sort_order`, timestamps.
- `App\Support\Reviews\MonthlyReviewStats`: bir ayın rakamlarını hesaplar (saf, test edilebilir).
- `App\Support\Reviews\ReviewSuggestions`: snapshot'tan öneri maddeleri üretir.

## 8. Adımlar

- [x] **Adım 1: Model ve rakamlar** (migration'lar, `MonthlyReview`, `ReviewItem`, factory'ler, `MonthlyReviewStats`, testler).
- [x] **Adım 2: Otomasyon** (`reviews:create` komutu ve zamanlaması, Kadir'e e-posta, `ReviewSuggestions`, testler).
- [x] **Adım 3: Ziyaretçi sayfası** (`/hedefler/aylik`, ay sayfası, sekmeler, rakam kutuları, üç sütun, geçen ayın denemeleri, `/hedefler`'deki kart, demo veri). *Kadir'in onayı beklenir (2026-10-09: yapıldı, onay bekliyor).*
- [ ] **Adım 4: Admin** (liste, düzenleme ekranı, öneriler, yeniden hesaplama, pano kartı, yedek sayımı).
- [ ] **Adım 5: Yorum, bildirim, RSS, paylaşım** (yorum bileşeninin genelleşmesi, `notify_monthly_reviews`, duyuru, RSS, OG görseli, sitemap).

## 9. Uygulama notları

*(Her adımda buraya eklenir.)*

### Adım 1 (2026-10-09)

- **Haftalık/aylık halka hangi aya ait:** Dönemin **bittiği** aya. 28 Eylül haftası (4 Ekim'de biter) Ekim'e, 26 Ekim haftası (1 Kasım'da biter) Kasım'a sayılır. Gün sayıları (`doneDays`) ise takvim ayına göre.
- **Rekor:** Sadece daha önce gerçek bir rekor varsa sayılır (`ChainPeriod::recordMinimum()`: 7 gün / 4 hafta / 3 ay), takipçi bildirimlerindeki gibi. Yoksa ilk ay her zincir "rekor" görünürdü.
- **Ziyaret:** `COUNT(DISTINCT visitor_hash)`, admin'deki ziyaretçi sayfasıyla aynı sayım. Hash günlük tuzlandığı için aynı kişi her gün yeni ziyaret sayılır; etiket "ziyaret", "ziyaretçi" değil.
- **En çok okunan yazı:** `/yazilar/{slug}` yolları arasında en çok görüntülenen ve hâlâ yayında olan yazı (`post_id` saklanır, başlık her gösterimde yeniden okunur). Taslağa düşen yazı atlanır.
- **Snapshot'ın şekli:** `MonthlyReviewStats` sınıfının PHPDoc'unda (`Stats` tipi). Hedefler `goal_id` ile tutulur, başlık hiç saklanmaz.

### Adım 2 (2026-10-09)

- **Komut:** `php artisan reviews:create` geçen ayı, `php artisan reviews:create 2026-09` verilen ayı oluşturur. Ay zaten varsa dokunmaz ve e-posta göndermez. Zamanlama: her ayın 1'i 00:15 (`routes/console.php`).
- **E-posta ve push:** `MonthlyReviewReady` ("Ekim değerlendirmesi hazır") önerileri de listeler. Push, `ManageGoals` izni olanlara gider. Adres yoksa ya da gönderim hata verirse taslak yine oluşur; admin panosu (adım 4) onu gösterir.
- **E-postadaki link** şimdilik admin panosuna gidiyor; adım 4'te düzenleme ekranına çevrilecek.
- **Öneri eşikleri:** Zincir %90 ve üstü tuttuysa "iyi giden", %50'nin altındaysa "zorlandığım". Arası önerilmez. Sayısal hedef "önde" ise iyi, "biraz geride" ise zor. Hiç yazı yoksa zor, iki ve üstü yazı iyi. Tek yazı önerilmez.
- **Ay adları** `TurkishDate::month()`, `monthYear()`, `inMonth()` ("Kasım'da"). `CarbonImmutable::locale()` PHPStan'da `static|string` döndüğü için Türkçe biçimlendirme hep bu sınıftan geçiyor.

### Adım 3 (2026-10-09)

- **Adresler:** `goals.reviews.index` (`/hedefler/aylik`, en son yayınlanan aya 302) ve `goals.reviews.show` (`/hedefler/aylik/{yyyy-mm}`). İkisi de `hedefler/{slug}`'tan önce kayıtlı; bu yüzden slug'ı `aylik` olan bir hedef sayfası açılamaz.
- **Veri:** `App\Support\Content\ReviewContent` (diğer `*Content` sınıfları gibi). Hedef başlıkları her gösterimde çözülür: sansürlü hedef `x-site.censored` ile karalanır (Yakın rolü ve admin başlığı görür), sonradan gizlenen hedefin kutusu kaybolur. En çok okunan yazı yayından kalkarsa satır görünmez.
- **Taslak:** Sadece `ManageGoals` iznine sahip olanlara, `noindex` ile. Geçen ay taslaksa onun denemeleri ziyaretçiye gösterilmez.
- **Tasarım:** Ay sekmeleri etiket filtresindeki washi bant stili (son 4 yayınlanmış ay, şu anki ay hep içinde). Puan, film notlarındaki kırmızı kalemli `x-site.grade`. Rakam kutularında HTML'de önce etiket (`dt`) sonra sayı (`dd`), sayıyı CSS `order` üste alır. Denenmeyenler `scribbled-out` + "olmadı", geçmiş yıllar arşivi gibi.
- **Hedefler sayfası:** Yıl ilerleme çubuğunun altında en son değerlendirmenin kartı (puan, ay, özet).
- **Demo veri:** `DemoSeeder::seedReviews()` son iki ayı (`data/demo-reviews.php`) demo hedeflerden hesaplanan rakamlarla yayınlar; ikinci ay ilkinin denemelerini işaretler.
- **Bilinen:** Yerelde ziyaret kaydı olmadığı için demo değerlendirmelerde "0 sayfa görüntüleme" görünür.
