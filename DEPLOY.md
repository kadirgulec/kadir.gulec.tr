# Deploy Öncesi Kontrol Listesi: kadir.gulec.tr

Siteyi ilk kez yayına almadan önce bu listedeki her madde işaretlenmiş olmalı. Ayrıntılar ve kararlar `ADMIN.md` içinde.

---

## 1. Kadir'in kararları ve metinleri

- [x] **Gerçek cihaz testi** (ADMIN.md Adım 9): telefon, Firefox, Safari, ekran okuyucu.
- [x] **Gizlilik ve künye** metinlerini bir Datenschutz-Generator'la ya da hukukçuyla kontrol ettir. Seçilen e-posta servisinin adını gizlilik notundaki yer tutucuya yaz (`resources/views/site/legal/privacy.blade.php`), `config/legal.php`'deki `updated_at`'i güncelle.
- [x] **Künye adresi:** `LEGAL_ADDRESS` (künyede posta adresi zorunlu; boşsa yer tutucu görünür). Şimdilik sadece "Düren", Kadir'in kararı.
- [x] **Hakkımda'nın gerçek metinleri** (`resources/views/site/about.blade.php`, `config/about.php`).

## 2. E-posta

- [x] Bir e-posta servisi seç (ör. Resend, AB bölgesi) ve gönderen adresi belirle.
- [x] DNS'e SPF, DKIM ve DMARC kayıtlarını ekle.
- [x] `.env`: `MAIL_MAILER`, `MAIL_HOST`, `MAIL_PORT`, `MAIL_USERNAME`, `MAIL_PASSWORD`, `MAIL_FROM_ADDRESS`, `MAIL_FROM_NAME`.
- [x] `.env`: `MAIL_CONTACT_ADDRESS` (iletişim formu mesajlarının gideceği adres; boşsa `LEGAL_EMAIL`).
- [x] Gerçek bir deneme: kayıt doğrulama e-postası, şifre yenileme, iletişim formu, bildirim özeti (`php artisan notifications:send instant`).

## 3. Production `.env`

- [x] `APP_ENV=production`, `APP_DEBUG=false`, `APP_URL=https://kadir.gulec.tr`
- [x] `APP_KEY` üretildi (`php artisan key:generate`), yerelinkiyle aynı değil.
- [x] `LOG_LEVEL=warning` (ya da `error`)
- [x] `DB_*`: sunucuda MariaDB 11.4 var, `utf8mb4_tr_0900_ai_ci` orada yok: `DB_COLLATION=utf8mb4_uca1400_turkish_ai_ci`, veritabanı `utf8mb4` / aynı sıralama.
- [x] `TURNSTILE_SITE_KEY` / `TURNSTILE_SECRET_KEY`: **gerçek** anahtarlar (yereldeki `1x000…` test anahtarları her şeyi geçirir), Cloudflare'de alan adı `kadir.gulec.tr`.
- [x] `TMDB_API_TOKEN`
- [x] `QUEUE_CONNECTION=database`
- [x] `ADMIN_STRONG_LOGIN` boş bırakılabilir (production'da kendiliğinden açık).

## 4. Sunucu

- [x] PHP 8.3+, eklentiler: `gd` (WebP ve FreeType desteğiyle), `zip`, `pdo_mysql`, `mbstring`, `intl`.
- [x] `mysqldump` ve `mysql` istemcileri kurulu (yedekleme ve geri yükleme bunları çağırır).
- [x] Yükleme sınırları yedek arşivleri için yeterli: PHP `upload_max_filesize` / `post_max_size` ve web sunucusu (`client_max_body_size`) en az 100 MB.
- [x] HTTPS sertifikası, `http` → `https` yönlendirmesi.
- [x] Cron (Hestia'da, her dakika): `/usr/bin/php8.4 ~/web/kadir.gulec.tr/public_html/artisan schedule:run >> /dev/null 2>&1`. Hestia'nın cron alanı `cd … &&` kabul etmediği için PHP'nin ve `artisan`'ın tam yolu yazılır. Bildirimler ve duyurular bununla çalışır; sunucuda Supervisor olmadığı için kuyruk da her dakika buradan boşaltılır (`routes/console.php`, yedek oluşturma bunu bekler).
- [x] `storage/` ve `bootstrap/cache/` web sunucusu kullanıcısına yazılabilir.

## 5. İlk kurulum komutları

- [x] `admin` dalı `master`'a birleştirildi, CI yeşil. İlk otomatik deploy kodu yükler, `migrate`, `permissions:sync`, `storage:link` ve `optimize`'ı çalıştırır (`deployment/remote.sh`).
- [x] `php8.4 artisan key:generate`, ardından `php8.4 artisan optimize`
- [x] ~~`php artisan db:seed --class=RealContentSeeder --force`~~ Çalıştırılmadı: projeler ve alet çantası admin panelinden elle dolduruluyor. (`DemoSeeder` production'da **çalıştırılmaz**.)
- [x] `php artisan user:create-admin`, ardından ilk girişte 2FA ya da passkey kur (production'da admin paneli bunu ister).
- [x] `php artisan optimize` (config, route, view, event önbellekleri); her deploy'da `remote.sh` da çalıştırıyor.

## 6. Yayından sonra

- [x] `/admin/yedekler`'den bir yedek oluştur ve indir (kuyruk çalışıyor mu?).
- [x] `/sitemap.xml`, `/robots.txt`, `/yazilar/rss` açılıyor; bir yazının bağlantısı paylaşıldığında OG görseli görünüyor.
- [x] Taslak bir yazı ziyaretçiye 404 veriyor; `/stil` ve `/admin/stil` production'da 404 veriyor.
- [x] Kayıt ve iletişim formunda Turnstile kutusu görünüyor ve gönderim çalışıyor.

## 7. PWA ve push bildirimleri

- [ ] `php artisan push:vapid` **bir kez** çalıştır, çıkan `VAPID_PUBLIC_KEY` ve `VAPID_PRIVATE_KEY`'i production `.env`'e yaz (`VAPID_SUBJECT` opsiyonel; boşsa `mailto:` + `LEGAL_EMAIL`). Anahtarlar sonradan değişirse bütün cihaz abonelikleri sessizce düşer. Anahtar yoksa site normal çalışır, sadece push gitmez ve "Bu cihaz" bölümü görünmez.
- [ ] `.env` elle değiştiği için ardından `php8.4 artisan optimize` (config önbelleği).
- [x] Sunucuda PHP `curl`, `openssl`, `mbstring` ve `gmp` ya da `bcmath` eklentileri (şifreleme için; `gmp` daha hızlı). Kontrol edildi 2026-10-05: PHP 8.4 CLI ve FPM'de `bcmath`, `curl`, `openssl`, `mbstring` var, `gmp` yok (kurmak `sudo` ister, gerek yok: `bcmath` ile şifreleme + imza yerelde ~20 ms).
- [ ] Gerçek cihaz denemesi: Android'de Chrome → "Ana ekrana ekle"; iPhone'da Safari → Paylaş → "Ana Ekrana Ekle", sonra uygulamadan aç. Hesabım → Bildirimler → "Bu cihazda bildirimleri aç" → "Deneme bildirimi gönder". Çıkış yapınca bildirim gelmemeli.

## Her deploy'da

`master`'a her push'ta `.github/workflows/deploy.yml` testleri çalıştırır; geçerse composer (`--no-dev`) ve `npm run build` CI'da yapılır, dosyalar `rsync` ile `~/web/kadir.gulec.tr/public_html`'e gider (`.env`, `storage/` ve `public/storage` dokunulmaz), sonra sunucuda `deployment/remote.sh`: `down` → `migrate --force` → `permissions:sync` → `optimize` → `queue:restart` → `up`. Bir adım hata verirse site yine açılır, workflow kırmızı olur. Elle de çalıştırılabilir (Actions → CI/CD → Run workflow).

- [ ] Büyük bir değişiklikten önce `/admin/yedekler`'den yedek al.
