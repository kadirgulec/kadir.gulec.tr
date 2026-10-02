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
- **Admin güvenliği:** Production'da admin `/admin` sayfalarına ancak passkey ile girmişse ya da 2FA açıksa erişir, yoksa güvenlik ayarlarına yönlendirilir. Yerelde bu şart aranmaz (`ADMIN_STRONG_LOGIN`, varsayılan: sadece `APP_ENV=production`'da açık; Kadir'in 2026-10-02 isteği). Üyelerde 2FA opsiyonel.
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
  7. *(Uygulamada eklendi)* Slug başlıktan üretildiği için, izni olmayan biri sansürlü bir hedefin adresini opak bir kimlikle görür (`k-12`, ör. `/hedefler/zincir/k-12`, `#hedef-k-12`); gerçek slug onlara 404 verir. Sansürlü hedefin görseli ve bağlı proje linki de gönderilmez. Gizli hedefler sitede admin dahil kimseye görünmez (admin panelde durur). Yeni hedefler gizli başlar.
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

- [x] **Adım 1: Temel**
  - [x] 1.1 Altyapı: CI, MySQL collation ve test veritabanı, `tr` dili ve çeviri dosyaları, `Europe/Berlin`.
  - [x] 1.2 Roller ve izinler: `spatie/laravel-permission`, `Permission` enum'u, seeder, `Gate::before`, `user:create-admin`, admin erişim middleware'i (passkey/2FA şartı).
  - [x] 1.3 Admin bileşen kütüphanesi, ikonlar, admin layout, pano iskeleti, `/admin/stil`.
  - [x] 1.4 Giriş, kayıt ve profil sayfaları defter tasarımında; Flux'ın kaldırılması.
  - [x] 1.5 Kullanıcılar ve roller ekranları.
- [x] **Adım 2: Projeler** (Markdown renderer, görsel servisi, teknolojiler, devlog, admin CRUD, ziyaretçi sayfaları gerçek veriden, prototip projelerin içe aktarılması)
- [x] **Adım 3: Yazılar** (kod renklendirme, etiketler, editör + önizleme + kılavuz, slug yönlendirmeleri, admin CRUD, ziyaretçi sayfaları)
- [x] **Adım 4: İzlediklerim** (TMDB, eser/izleme/sezon, yorum, afiş rengi, admin CRUD, ziyaretçi sayfaları)
- [x] **Adım 5: Hedefler** (tek tablo, zincir günleri, ilerleme, taşlar, görünürlük kuralları ve testleri, pano "Bugün" kartı, ziyaretçi sayfaları, `PrototypeContent`'in silinmesi)
- [x] **Adım 6: Üyeler** (kayıt, yorumlar ve moderasyon, Turnstile, takip, bildirimler ve özet e-postaları, gizlilik/künye, veri indirme, hesap silme)
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

- **Erişim (1.2):** `App\Enums\Permission` (kodda tanımlı izinler, `group()` ile roller ekranındaki başlık), `App\Enums\SystemRole` (admin / member / close; `name` sabit, `roles.label` panelden değişebilir). `App\Models\Role` Spatie'nin Role modelini genişletir. `App\Actions\Access\SyncPermissions` izin tablosunu enum'la eşitler; bir veri migration'ı her temiz veritabanında (testler dahil) çalıştırır, deploy'da `php artisan permissions:sync`. `Gate::before` admin'e her şeyi açar. `/admin` route grubu `admin` middleware'i (`EnsureAdminAccess`) ile korunur: izni olmayana 404, zayıf girişe (2FA yok, passkey yok) güvenlik ayarlarına yönlendirme. Passkey girişi `RememberPasskeyLogin` listener'ı ile oturuma işaretlenir (`User::PASSKEY_SESSION_KEY`). Girişler artık `/`'e yönlenir (`fortify.home`). Factory durumları: `admin()` (2FA'lı), `member()`, `close()`, `blocked()`.
- **Admin arayüzü (1.3):** `resources/css/admin.css` + `resources/js/admin.js` (tema düğmesi: açık / koyu / sistem, aynı `theme` anahtarı). Layout `resources/views/layouts/admin.blade.php` (`#[Layout('layouts::admin')]`). Bileşenler `resources/views/components/admin/*`: button, input, textarea, select, checkbox, switch, field, error, card, badge, heading, text, link, separator, empty, table (columns, column, rows, row, cell), pagination, modal (trigger, close), dropdown (item, separator), tooltip, toasts, nav-item, page-header, icon. Form kontrolleri adı `wire:model`'den alır, hatayı `$errors`'tan gösterir, `aria-invalid` / `aria-describedby` bağlar (`App\Support\FormControl`). İkonlar `resources/icons/lucide.php` (Lucide 1.50, ISC); yeni ikon gerekirse SVG içi bu dosyaya eklenir. Toast: `$this->dispatch('toast', text: …, variant: …)` ya da yönlendirmeden önce `session()->flash('toast', [...])`. Modal: `modal-show` / `modal-close` tarayıcı olayları; `<dialog wire:ignore.self>`. Sayfalar Livewire SFC'leri (`resources/views/pages/admin/⚡*.blade.php`); PHPStan SFC'leri analiz etmediği için **iş mantığı `app/Actions` ve `app/Livewire/Forms` altında** durur, sayfalar ince kalır. Bölüm renkleri admin'de sadece nokta olarak (`Section::adminDotClass()`). `/admin/stil` sadece production dışında.
- **Giriş ve hesap sayfaları (1.4):** Fortify görünümleri (`resources/views/pages/auth/*`) `layouts/auth.blade.php` ile defter sayfasında bantlı bir not kartı içinde. Bu sayfalar düz Blade olduğu için layout `@livewireScripts` ile Alpine'ı elle yükler. "Hesabım" sayfaları `/hesap` (profil, hesap silme) ve `/hesap/guvenlik` (şifre, 2FA, kurtarma kodları, passkey'ler); Livewire SFC'leri `#[Layout('layouts::account')]` kullanır, bileşen adları ve metotları starter kit'tekiyle aynı kaldı. Defter tarafının form bileşenleri: `x-site.form.input` (göster/gizle düğmeli şifre), `checkbox`, `button` (primary / secondary / danger / link), `error` (kırmızı kalem), `status`; `x-site.modal`. Tema sayfası kaldırıldı (lamba var). Footer'da "giriş yap" / "hesabım" linki. `livewire/flux`, `app.css`, `app.js`, Instrument Sans ve starter kit layout'ları kaldırıldı.
- **Kullanıcılar ve roller (1.5):** `/admin/kullanicilar` (arama, rol filtresi, sıralama), `/admin/kullanicilar/{user}` (roller, engel, silme), `/admin/roller`, `/admin/roller/{role}` (ad, gruplu izinler). Kurallar `app/Actions/Users` (`UpdateUserRoles`: kendi admin rolünü kaldıramazsın, son admin rolünü kaybedemez; `BlockUser`: kendini ve admin'i engelleyemezsin; `DeleteUser`: kendini buradan silemezsin, son admin silinemez) ve `app/Actions/Roles` (`SaveRole`: yeni rolün makine adı etiketten, görünen ad tekrar edemez, admin'in izinleri seçilmez; `DeleteRole`: sistem rolleri silinemez). Route'lar `can:users.manage` / `can:roles.manage` ile korunur; `EnsureAdminAccess` Livewire'ın persistent middleware listesinde, yani `/livewire/update` isteklerinde de çalışır.
- **Markdown (2):** `App\Support\Markdown\Markdown` (`toHtml`, `toText`, `excerpt`, `readingMinutes`). Dipnotlar `SidenoteRenderer` ile referans verilen yerde Tufte kenar notu olur, dipnot bölümü basılmaz. `html_input: escape`, güvensiz linkler düşer, dış linklere `rel="noopener"`. Modeller `RendersMarkdown` trait'iyle kayıtta HTML üretir (`markdownColumns()`: `['body' => 'body_html']`). Sitede `.prose-notebook` (site.css) tipografisi; kısa girdiler için `.prose-compact`.
- **Görseller (2):** `App\Support\Images\ImageStore` (`intervention/image` v4, GD): her yükleme EXIF'e göre döndürülür, meta veri silinir, 480/960/1600 px WebP olarak `public` diske yazılır, büyütülmez. Modelde sadece taban yol (`projects/01j…`), URL ve `srcset` için `ImageStore::url()` / `srcset()`. `public` diskin URL'i göreli (`/storage`); mutlak adres gereken yerde `url()`.
- **Ortak model davranışları (2):** `HasPublication` (`published()` scope'u, `publicationState()`: taslak / zamanlanmış / yayında), `HasSlugRedirects` (addan benzersiz slug; yayındaki bir kaydın slug'ı değişince `redirects` tablosuna 301, zincir oluşmaz; 404 olduğunda `bootstrap/app.php` bu tabloya bakar), `Sortable` (`moveTo()`, `nextSortOrder()`). Polimorfik sütunlar morph map kullanır (`user`, `project`, …).
- **Projeler (2):** Modeller `Project`, `ProjectImage`, `Technology` (`toolbox_group`: her gün / ara sıra / diller → Hakkımda'daki alet çantası), `DevlogEntry` (polimorfik). Ziyaretçi sayfaları `App\Support\Content\ProjectContent` üzerinden prototipteki dizi şeklini alır; taslaklar sadece `projects.manage` iznine sahip olana "Taslak · sadece sen görüyorsun" bandıyla görünür. Admin: `/admin/projeler` (sürükle-bırak sıra), `/admin/projeler/yeni`, `/admin/projeler/{id}` (temel bilgiler, teknoloji combobox'ı, vaka çalışması editörü, galeri, devlog, yayın, kapak). Form mantığı `App\Livewire\Forms\ProjectForm` + `App\Actions\Projects\SaveProject`. Yeni bileşenler: `x-admin.markdown` (araç çubuğu, ⓘ kılavuz, iframe önizleme: `POST /admin/onizleme`), `x-admin.combobox`, `x-admin.file-upload` (`$upload` + `wire:drop.file`). Tek öne çıkan proje kuralı modelde. Gerçek içerik: `php artisan db:seed --class=RealContentSeeder` (6 proje, ekran görüntüleri, alet çantası; CoMon taslak olarak gelir). Proje → yıllık hedef bağlantısı 5. adımda gelecek.
- **Yazılar (3):** `Post` (Markdown gövde, okuma süresi kayıtta hesaplanır, özet boşsa `excerptText()` ilk paragraftan üretir) ve `Tag` (washi tape rengi slug'dan; `mergeInto()`). Ziyaretçi tarafı `App\Support\Content\PostContent` (yayındakiler, en yeni iki öne çıkan, etiket sayıları, önceki/sonraki, en çok ortak etikete göre ilgili yazılar). Kod blokları `CodeBlockRenderer` ile `tempest/highlight` renkleriyle koyu karta (`.code-card`, renkler koyu kartta AA). Tek satırdaki görsel `PolaroidRenderer` ile bantlı polaroid olur (`![açıklama](adres "altyazı")`), depodaki görsellere `srcset` eklenir; açıklaması boş ya da "açıklama yaz" olan görselle kayıt reddedilir. Admin: `/admin/yazilar` (arama, durum filtresi), `/admin/yazilar/yeni`, `/admin/yazilar/{id}` (editörde görsel ekleme düğmesi: yükler, imlece `![açıklama yaz](…)` yazar ve açıklamayı seçer), `/admin/yazilar/etiketler` (yeniden adlandır, birleştir, sil). Yerel örnek içerik `DemoSeeder` (`database/seeders/data/demo-posts.php`), sadece production dışında. `DatabaseSeeder` model olaylarını kapatmaz (Markdown ve slug olaylara bağlı). Admin'deki monospace alanlarda ligatür kapalı (`==` ve `->` olduğu gibi görünsün).
- **İzlediklerim (4):** `Watchable` (eser; TMDB bilgileri yerelde, tek güncel puan, favori, yorum ve ayrı `review_published_at`, dizi durumu/sezon/bölüm), `Viewing` (günlük satırı: tarih, yer, not; tekrar izleme hesaplanır), `Season` (bölüm sayısı, opsiyonel puan ve not). `App\Support\Tmdb\Tmdb` (arama, ayrıntılar, Türkçe özet yoksa İngilizce, afiş indirme; sadece admin'de, `config('services.tmdb.token')`). `App\Actions\Watched\ImportFromTmdb` (yeni kayıt ya da "TMDB'den yenile": bilgiler ve sezon bölüm sayıları güncellenir, puan/yorum/notlar/izlemeler korunur, afiş sadece yoksa indirilir). `StorePoster` + `App\Support\Images\PosterPalette` (afişin en baskın canlı rengi bir kere hesaplanır; gri afişte bölüm rengi). Yorum blokları CommonMark eklentisi: `:::spoiler … :::`, `:::replik Kişi … :::` (`App\Support\Markdown\Containers`); günlükteki yorum özetine spoiler girmez. Ziyaretçi tarafı `App\Support\Content\WatchedContent` (günlük = her izleme, raf = devam eden diziler, detay = eser). TMDB atfı `x-site.tmdb-attribution` (logo `public/images/tmdb.svg`, "Alt short", değiştirilmeden). Admin: `/admin/izlediklerim`, `/admin/izlediklerim/ekle` (TMDB araması, aynı kayıt ikinci kez eklenmez, elle ekleme), `/admin/izlediklerim/{id}` (görüşüm, yorum, izleme günlüğü, sezonlar, bilgiler, afiş ve vurgu rengi, yayın). Prototipin örnek film/dizileri `DemoSeeder`'da (`demo-watched.php`; afişler varsa `public/images/prototype/posters`). `InlineMarkup` kaldırıldı (yerini `Markdown` aldı, testleri `MarkdownTest`'te).
- **Hedefler (5):** Tek `goals` tablosu (`App\Enums\GoalKind`: zincir / yıllık / uzun vade; `GoalMeasure`; `GoalVisibility`), `goal_milestones`, `goal_progress` (sayısal hedefte `current()` = toplam), `chain_days` (`ChainDayState`: tamam / mazeretli; satırı olmayan gün kopuk). `Goal::chainHistory()`: başlangıçtan bugüne günler, bugün işaretlenmediyse dahil edilmez (gün bitmedi, seri sabah kırılmaz). Uzun vadeli hedefin güncellemeleri `devlog_entries` (polimorfik). Ziyaretçi tarafı `App\Support\Content\GoalContent`: görünürlük kuralları tek yerde, `goals.view-censored` izni her seferinde sorulur (önbelleğe alınmaz, controller örneği istekler arasında yaşayabiliyor). `GoalCensor` sansürlü hedefin metinlerini, notlarını, görselini ve proje linkini çıkarır. Admin: `/admin/hedefler` (üç bölüm, sürükle-bırak sıra, yıl seçici, "önceki yıldan kopyala"), `/admin/hedefler/yeni/{zincir|yillik|uzun-vade}`, `/admin/hedefler/{id}` (zincirde tıklanabilir yıllık ızgara; yıllıkta ilerleme kayıtları / kilometre taşları / BAŞARILDI; uzun vadede neden, görsel, güncellemeler, bağlı hedefler). Kurallar `App\Actions\Goals\MarkChainDay` (gelecek ve süre dışı günler kilitli; `cycle()` ızgara, `toggle()` pano) ve `CopyYearlyGoals`; üst hedef kuralı `GoalForm`'da (sadece uzun vade, uzun vadenin üstü yok). Pano: "Bugün" kartı (her aktif zincir için bugün Tamam/Mazeret, dün tek dokunuş; sayısal hedeflere notlu +1) ve taslak sayıları. Projeler formunda "bağlı yıllık hedef" seçimi. Hakkımda'nın hayat durakları `config/about.php`'de. `PrototypeContent` silindi; örnek hedefler `DemoSeeder`'da (`demo-goals.php`).
- **Yorumlar ve spam (6a):** `Comment` (polimorfik, tek seviye cevap, soft delete, `approved_at`; yazarı silinirse `user_id` null → "silinmiş üye"). Kurallar `App\Actions\Comments\PostComment` (doğrulanmış, engelsiz, `comments.create` izinli üye; ilk yorum onay bekler, onaylı yorumu olan ya da moderatör doğrudan; cevaba cevap aynı ana yoruma; dakikada 5, günde 30) ve `ModerateComment` (cevabı olan silinen yorum "bu not silindi" olarak kalır, olmayan tamamen silinir). Metin `App\Support\CommentFormatter` ile: önce escape, sonra satır sonu ve `rel="nofollow ugc noopener"` linkler. Sitede `<livewire:site.comments>` (yazı sayfasının altında, 15 dakika düzenleme, Kadir'in notlarında "kg" mührü). Admin `/admin/yorumlar` (bekleyenler, onayla / sil / yazarı engelle; `comments.moderate`); kullanıcı listesinde yorum sayısı, kullanıcı sayfasında son yorumlar. Spam: `App\Rules\Honeypot` (`website` alanı) ve `App\Rules\Turnstile` (siteverify; secret yoksa atlanır) kayıt ve yorum formlarında, `x-site.form.bot-check` bileşeni (Livewire'da token bir özelliğe yazılır, gönderimden sonra widget sıfırlanır). Testler Turnstile/TMDB'ye bağlanmaz (`phpunit.xml`'de anahtarlar boş). Gizlilik notu `/gizlilik` ve künye `/kunye` (taslak; adres `config/legal.php` / `LEGAL_ADDRESS`), footer'da linkler.
- **Takip ve bildirimler (6b):** `Follow` (polimorfik: film/dizi, hedef, proje; `HasFollowers` trait'i, silinince takipler de gider), kullanıcıda `notification_frequency` (`NotificationFrequency`: hemen / günlük / haftalık / hiç) ve `notify_new_posts`. Olaylar `App\Support\Notifications\Announcements` içinde model olaylarına bağlı (`register()`): yeni izleme, sezon notu, dizi durumu, proje durumu, devlog, uzun vadeli güncelleme, ilerleme (%50, ulaşıldı), kilometre taşı, BAŞARILDI, zincir (30/100/365 ve koşu başına bir rekor, en az 7 günü geçince), yorum cevabı. Zamanı gelen yazılar/yorumlar ve dün kopan zincirler (en az 3 gün) `notifications:announce` komutuyla (`announced_at`, `review_announced_at`; mevcut yayınlar migration'da duyurulmuş sayılır). `App\Support\Notifications\Notifier` her takipçiye kendi `notification_items` satırını yazar: başlık o kişiye göre (sansürlü hedef izinsiz kişiye "🔒 ██████" ve `k-{id}` adresiyle), gizli hedef ve taslak sessiz, e-posta almayan (doğrulanmamış, engelli, "hiç") kişiye satır yazılmaz, `key` tekrarları engeller. Gönderim `notifications:send {instant|daily|weekly}` (`routes/console.php`: her 5 dk, her gün 18:00, pazartesi 18:00) tek bir `NotificationDigest` e-postası; `List-Unsubscribe` + `List-Unsubscribe-Post` (RFC 8058), öğe başına "takipten çık". İmzalı linkler: `/bildirimler/kapat/{user}`, `/takip/birak/{follow}` (GET onay, POST işlem; CSRF dışı). Sitede `<livewire:site.follow-button type id>` (film/dizi, zincir, yıllık kart, uzun vade, proje) ve yazılarda `<livewire:site.post-subscription>`. Hesabım: `/hesap/takip`, `/hesap/bildirimler`. Admin'de kullanıcı sayfasında takipler ve sıklık. Sunucuda cron: `* * * * * php artisan schedule:run`.
- **Veriler ve hesap silme (6c):** `/hesap/verilerim` (`DataExportController`): profil, roller, bildirim tercihleri, passkey adları, yorumlar (silinmişler dahil), takipler ve bekleyen bildirimler JSON olarak; şifre özeti ve güvenlik anahtarları dosyada yok. Hesap silinince yorumlar `user_id = null` ile "silinmiş üye" olarak kalır, takipler ve bildirim satırları silinir. Gizlilik notu bunları anlatıyor.

