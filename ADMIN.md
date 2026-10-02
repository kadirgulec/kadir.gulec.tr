# Admin ve Üyelik Planı: kadir.gulec.tr

Bu dosya, admin paneli ve üyelik aşamasının kararlarını, adımlarını ve uygulama notlarını tutar. Tasarım kararları `TASARIM.md` içinde. Yeni bir oturumda önce bu dosyayı oku, sonra işaretlenmemiş ilk adımdan devam et.

> Kararlar 2026-10-02 tarihli soru-cevap oturumunda alındı. Bütün adımlar `admin` dalında, her adım ayrı commit olarak yapılıyor.

---

## 1. Erişim ve yetki

- **Paket:** `spatie/laravel-permission`. İzinler kodda tanımlıdır (`App\Enums\Permission` + seeder). Admin panelinden yeni izin türü oluşturulmaz, sadece roller oluşturulur ve rollere izin atanır.
- **Roller:**

  | Rol | Kim | Ne yapabilir |
  |---|---|---|
  | **Admin** | Kadir | Her şey (`Gate::before`). Düzenlenemez. |
  | **Üye** | Kayıt olan herkes (otomatik atanır) | Yazılara yorum, kendi yorumunu düzenleme/silme, takip. Silinemez. |
  | **Yakın** | Elle atanan kişiler | Üye yetkileri + sansürlü hedeflerin gerçek metnini görme (`goals.view-censored`) |

- **Kayıt:** Açık, e-posta doğrulaması zorunlu. Üyelik adımına kadar kapalı tutulur.
- **İlk admin:** `php artisan user:create-admin`. Seeder'da gerçek şifre yok.
- **Admin güvenliği:** Admin `/admin` sayfalarına ancak passkey ile girmişse ya da 2FA açıksa erişir, yoksa güvenlik ayarlarına yönlendirilir. Üyelerde 2FA opsiyonel.
- **Kullanıcı yönetimi:** Liste (arama, rol filtresi), detay (rol, yorumlar, takipler), engelleme (`blocked_at`: giriş yapabilir, yorum yazamaz, e-posta almaz, yorumları gizlenir), silme. Kendi admin rolünü kaldırma, kendini engelleme ve son admini silme engellenir. Audit log yok.
- **Roller ekranı:** Rol oluştur, yeniden adlandır, sil; izinler gruplu onay kutularıyla.

## 2. Arayüz

- **Admin:** `/admin` altında, Türkçe. Nötr "arka ofis" görünümü: zinc tonları, beyaz kartlar, ince kenarlıklar. Nunito Sans + JetBrains Mono; Fraunces/Caveat sadece logoda. Kenar çubuğunda bölüm renkli noktalar, butonlar ve odak halkaları mürekkep laciverti. Dark mode sistem ayarını takip eder, geçiş düğmesi var, aynı `theme` anahtarı.
- **Kenar çubuğu:** Pano · İçerik (Yazılar, İzlediklerim, Hedefler, Projeler) · Üyeler (Kullanıcılar, Roller, Yorumlar) · Yedekler · Siteyi gör ↗.
- **CSS:** `resources/css/admin.css` (site.css'e dokunulmaz).
- **Bileşenler:** `resources/views/components/admin/*` (`<x-admin.button>` …). Flux'a yakın API (label/description/error otomatik, `wire:model` adından hata), Alpine ile etkileşim, ek JS kütüphanesi yok, tarayıcının kendi date/color input'ları. İhtiyaç doğdukça yazılır.
- **İkonlar:** `<x-admin.icon name="…">`, Lucide SVG'leri (ISC lisansı) elle kopyalanır. Paket yok.
- **Stil rehberi:** `/admin/stil` (sadece production dışında).
- **Giriş, kayıt, şifre, e-posta doğrulama, profil ayarları:** Defter tasarımında (`site.css`), Türkçe.
- **Flux:** Tamamen kaldırılır (`livewire/flux` bağımlılığı dahil).

## 3. İçerik

### Ortak

- **Metin formatı:** Her yerde Markdown (`league/commonmark`): standart Markdown, `[^1]` → kenar notu, `==metin==` → fosforlu kalem, yorumlarda `:::spoiler … :::` ve `:::replik Kişi … :::`. Ham HTML kapalı. HTML kayıt sırasında bir kere üretilip `*_html` sütununa yazılır. Okuma süresi ve otomatik özet de kayıtta hesaplanır.
- **Kod renklendirme:** `tempest/highlight`, sunucu tarafında, kayıt anında. Renkler kendi paletimiz (WCAG AA).
- **Editör:** Monospace textarea + araç çubuğu + "Önizleme" sekmesi (sitenin CSS'iyle). Etiketin yanında ⓘ ikonu: hover/tıklama/odakta kısa söz dizimi kılavuzu (`<x-admin.tooltip>`).
- **Görseller:** Livewire yükleme, `intervention/image` (GD): EXIF dönüşü düzeltilir, EXIF silinir, 480/960/1600 px WebP. `public` disk, yollar modelde sütun. Yazı içi görsel: "görsel ekle" imlece `![açıklama](…)` yazar, alt metin zorunlu. Kayıt/görsel silinince dosyalar da silinir.
- **Yayın durumu:** `published_at` (boş: taslak, gelecekte: zamanlanmış, geçmişte: yayında). Taslaklar sitede sadece admin'e görünür ("TASLAK" bandı), ziyaretçiye 404.
- **Slug:** Başlıktan otomatik (Türkçe karakterler dönüştürülür). Yayındaki içeriğin slug'ı değişirse `redirects` tablosu üzerinden 301.
- **Meta:** Formlarda opsiyonel "SEO açıklaması", boşsa otomatik.

### Projeler

- `projects`: name, slug, tagline, status (yapım aşamasında / yayında / arşiv), started_year, cover_path, demo_url, repo_url, body/body_html (vaka çalışması), is_featured (tek, yenisi eskisini kaldırır), published_at, sort_order (sürükle-bırak), goal_id (bağlı yıllık hedef).
- `project_images`: path, caption, sort_order.
- `technologies` (name, slug, logo, in_toolbox): projelerle çoktan çoğa; Hakkımda'daki alet çantasını da besler.
- `devlog_entries` (polimorfik: proje ve uzun vadeli hedef): date, body/body_html.

### Yazılar

- `posts`: title, slug, excerpt (opsiyonel, boşsa ilk paragraftan ~160 karakter), body/body_html, reading_minutes, is_featured, published_at.
- `tags` (name, slug): renk slug'dan türetilir. Formda combobox, yoksa anında oluşturulur. Ayrı etiket ekranı (yeniden adlandır, birleştir, sil).
- Öne çıkan: işaretli olanlardan en yeni 2'si. İlgili yazılar: en çok ortak etiket, eşitlikte en yeni, en fazla 3.

### İzlediklerim

- **TMDB içe aktarma:** Admin'de Türkçe arama, seçilen kaydın bilgileri yerelde saklanır (adlar, yıl, yaratıcı, türler, süre, özet, 6–8 oyuncu, sezonlar). Afiş indirilir, WebP'ye çevrilir, vurgu rengi bir kere hesaplanır. Sayfa açılışında TMDB'ye istek yok. "TMDB'den yenile" düğmesi. Her alan elle düzenlenebilir, tamamen elle giriş de mümkün. `TMDB_API_TOKEN` (.env).
- `watchables`: eser (TMDB bilgileri + rating, is_favorite, review/review_html, review_published_at, published_at, series_status, current_season, current_episode).
- `viewings`: watchable_id, watched_on, place, note. Tekrar izleme hesaplanır. Dizilerde sadece anlamlı anlar (başladım, bitirdim, S2'yi bitirdim).
- `seasons`: number, episode_count, rating, note.
- Puan eser başına tek (güncel görüş).
- **Atıf:** TMDB "Alt short (blue)" logosu, değiştirilmeden, `public/images/tmdb.svg`. Detay sayfalarının altında: "Yapım bilgileri ve afişler TMDB'den alınmıştır. Bu site TMDB tarafından onaylanmış veya desteklenmemektedir." Liste footer'ında daha küçük.

### Hedefler

- Tek `goals` tablosu: kind (zincir / yıllık / uzun vade), title, slug, visibility, parent_id, sort_order; yıllık: year, measure (sayısal / kilometre taşlı / evet-hayır), target, unit, achieved_at, show_progress_notes; uzun vade: why/why_html, image_path, started_year; zincir: started_on, ended_on.
- `goal_milestones`: title, done_at, sort_order. `goal_progress`: date, amount, note (sayısal hedeflerde `current` bunların toplamı). `chain_days`: date, state (tamam / mazeretli), note. Kopuk günler saklanmaz.
- Üst hedef sadece uzun vadeli bir hedef olabilir, uzun vadenin üstü olmaz (tek seviye).
- **Saat dilimi:** `Europe/Berlin`. Panoda "Bugün" kartı: her aktif zincir için Tamam/Mazeret, dün de işaretlenebilir, tekrar tıklama geri alır. Zincir düzenleme ekranında yıllık ızgara tıklanabilir (kopuk → tamam → mazeretli → kopuk), gelecek kilitli.
- **Yıl sonu:** Hiçbir şey taşınmaz; başarı hesaplanır. Ocak'ta "Geçen yılın hedeflerini kopyala".
- **Görünürlük kuralları:**
  1. Sansürlü hedefin başlığı, nedeni, güncellemeleri, taş adları ve ilerleme notları izinsiz kişiye gönderilmez; sayılar ve şekiller görünür.
  2. Üst hedef sansürlüyse etiket karalanır, gizliyse etiket görünmez. Gizli üst hedefin altındaki açık hedefler normal görünür ama ağaçta yer almaz.
  3. Proje bağlantısı da aynı kurala uyar.
  4. Sansürlü hedef izinsiz üyelerce takip edilebilir, e-posta da sansürlü gider. Gizli hedef takip edilemez; sonradan gizlenen hedefin aboneliği sessize alınır.
  5. Görünürlük değişikliği bildirim üretmez.
  6. Her kural ziyaretçi, üye, Yakın ve admin için feature testiyle korunur.

### Hakkımda

- Metinler kodda kalır (Blade + config). Alet çantası `technologies.in_toolbox`'tan gelir.

### Prototip verileri

- Örnekler factory'lere ve yerel `DemoSeeder`'a taşınır. 6 gerçek proje bir kere içe aktarılır. Sonunda `PrototypeContent` silinir.

## 4. Üyeler

- **Yorumlar:** Sadece yazılarda (tablo polimorfik). Sadece doğrulanmış üyeler. İlk yorum onay bekler, sonrakiler doğrudan yayınlanır. Admin kuyruğu: onayla / sil / engelle. Tek seviye cevap, Kadir'in cevapları "kg" mührüyle. Düz metin (satır sonu + otomatik link, `rel="nofollow ugc"`). 15 dakika düzenleme, her zaman silme; cevabı olan silinmiş yorum "silindi" olarak kalır.
- **Spam:** Honeypot + hız sınırı + Cloudflare Turnstile (kayıt ve yorum formları). Yerelde Cloudflare'in her zaman geçen test anahtarları.
- **Takip ve bildirimler:** E-posta (bildirim zili yok).

  | Takip | Ne zaman |
  |---|---|
  | Film/dizi | Yorum yayınlandı, yeni izleme, sezon notu, dizi durumu değişti |
  | Hedef | Zincir: rekor, 30/100/365, kopma. Yıllık: %50, ulaşıldı, kilometre taşı. Uzun vade: güncelleme |
  | Proje | Yeni devlog, durum değişikliği |
  | Yazılar | Yeni yazı (tek abonelik), yorumuna cevap |

  Olaylar biriktirilir; tercih: anında / günlük özet (varsayılan) / haftalık özet. Kuyruk + zamanlanmış komut. İmzalı tek tık abonelikten çıkma, `List-Unsubscribe`. Defter havasında sade HTML e-posta.
- **Gizlilik ve künye:** `/gizlilik` ve `/kunye`, taslak metinleri Claude yazar, Kadir kontrol ettirir. Analitik/izleme çerezi yok, çerez bandı yok. Yorumlarda IP saklanmaz.
- **Hesap silme:** Yorumlar anonimleşir ("silinmiş üye"). Profilde "verilerimi indir" (JSON).

## 5. Altyapı

- **Sunucu:** Kendi VPS'i. Kuyruk Supervisor ile, scheduler cron ile, görseller yerel diskte.
- **Veritabanı:** MySQL 8, `utf8mb4_tr_0900_ai_ci`. Testler ayrı `kadir_gulec_tr_testing` MySQL veritabanında.
- **Dil:** `APP_LOCALE=tr`, `APP_FALLBACK_LOCALE=en`, `APP_FAKER_LOCALE=tr_TR`. `lang/tr` elle Türkçeleştirilir (paket yok). Site mesajları samimi, admin mesajları düz.
- **Paylaşım/SEO:** Meta açıklamaları; kayıtta bir kere üretilen defter tarzı 1200×630 OG görselleri (`intervention/image`); `/yazilar/rss` (Atom, tam metin); `/sitemap.xml` (sadece yayında, açık içerik); robots.txt (`/admin`, giriş, profil hariç).
- **Yedekleme:** Admin'de "Yedekler": [Yedek oluştur] kuyrukta zip üretir (`database.sql` + `storage/` + `manifest.json`), imzalı kısa ömürlü indirme. İçe aktarma: manifest ve migration uyumu kontrolü, özet ekranı, şifre + `GERİ YÜKLE` onayı, öncesinde otomatik yedek, bakım modu. Gece otomatik yedek yok. Kendi kodumuz (`mysqldump` + `ZipArchive`).
- **CI:** `tests.yml` dalı `master`, MySQL servisi.

## 6. Adımlar

- [ ] **Adım 1: Temel**
  - [x] 1.1 Altyapı: CI, MySQL collation ve test veritabanı, `tr` dili ve çeviri dosyaları, `Europe/Berlin`.
  - [ ] 1.2 Roller ve izinler: `spatie/laravel-permission`, `Permission` enum'u, seeder, `Gate::before`, `user:create-admin`, admin erişim middleware'i (passkey/2FA şartı).
  - [ ] 1.3 Admin bileşen kütüphanesi, ikonlar, admin layout, pano iskeleti, `/admin/stil`.
  - [ ] 1.4 Giriş, kayıt ve profil sayfaları defter tasarımında; Flux'ın kaldırılması.
  - [ ] 1.5 Kullanıcılar ve roller ekranları.
- [ ] **Adım 2: Projeler** (Markdown renderer, görsel servisi, teknolojiler, devlog, admin CRUD, ziyaretçi sayfaları gerçek veriden, prototip projelerin içe aktarılması)
- [ ] **Adım 3: Yazılar** (kod renklendirme, etiketler, editör + önizleme + kılavuz, slug yönlendirmeleri, admin CRUD, ziyaretçi sayfaları)
- [ ] **Adım 4: İzlediklerim** (TMDB, eser/izleme/sezon, yorum, afiş rengi, admin CRUD, ziyaretçi sayfaları)
- [ ] **Adım 5: Hedefler** (tek tablo, zincir günleri, ilerleme, taşlar, görünürlük kuralları ve testleri, pano "Bugün" kartı, ziyaretçi sayfaları, `PrototypeContent`'in silinmesi)
- [ ] **Adım 6: Üyeler** (kayıt, yorumlar ve moderasyon, Turnstile, takip, bildirimler ve özet e-postaları, gizlilik/künye, veri indirme, hesap silme)
- [ ] **Adım 7: Yedekleme** (dışa/içe aktarma)
- [ ] **Adım 8: Paylaşım/SEO** (meta, OG görselleri, RSS, sitemap, robots.txt)
- [ ] **Adım 9: Gerçek cihaz testi** (telefon, Firefox/Safari, ekran okuyucu; Kadir yapar)

## 7. TODO (Kadir)

- [ ] E-posta servisi seç (ör. Resend, AB bölgesi), gönderen adresi belirle, DNS'e SPF/DKIM/DMARC ekle, `.env`'deki `MAIL_*` alanlarını doldur. O zamana kadar yerelde `MAIL_MAILER=log`.
- [ ] Production için Cloudflare Turnstile'ın gerçek anahtarları.
- [ ] Gizlilik ve künye metinlerini bir Datenschutz-Generator'la ya da hukukçuyla kontrol ettir.
- [ ] Hakkımda'nın gerçek metinleri.
- [x] TMDB API token'ı (`.env`'de).

## 8. Uygulama notları

*(Her adımda buraya eklenir.)*
