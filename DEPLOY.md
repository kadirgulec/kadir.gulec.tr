# Deploy Öncesi Kontrol Listesi: kadir.gulec.tr

Siteyi ilk kez yayına almadan önce bu listedeki her madde işaretlenmiş olmalı. Ayrıntılar ve kararlar `ADMIN.md` içinde.

---

## 1. Kadir'in kararları ve metinleri

- [ ] **Gerçek cihaz testi** (ADMIN.md Adım 9): telefon, Firefox, Safari, ekran okuyucu.
- [ ] **Gizlilik ve künye** metinlerini bir Datenschutz-Generator'la ya da hukukçuyla kontrol ettir. Seçilen e-posta servisinin adını gizlilik notundaki yer tutucuya yaz (`resources/views/site/legal/privacy.blade.php`), `config/legal.php`'deki `updated_at`'i güncelle.
- [ ] **Künye adresi:** `LEGAL_ADDRESS` (künyede posta adresi zorunlu; boşsa yer tutucu görünür).
- [ ] **Hakkımda'nın gerçek metinleri** (`resources/views/site/about.blade.php`, `config/about.php`).

## 2. E-posta

- [ ] Bir e-posta servisi seç (ör. Resend, AB bölgesi) ve gönderen adresi belirle.
- [ ] DNS'e SPF, DKIM ve DMARC kayıtlarını ekle.
- [ ] `.env`: `MAIL_MAILER`, `MAIL_HOST`, `MAIL_PORT`, `MAIL_USERNAME`, `MAIL_PASSWORD`, `MAIL_FROM_ADDRESS`, `MAIL_FROM_NAME`.
- [ ] `.env`: `MAIL_CONTACT_ADDRESS` (iletişim formu mesajlarının gideceği adres; boşsa `LEGAL_EMAIL`).
- [ ] Gerçek bir deneme: kayıt doğrulama e-postası, şifre yenileme, iletişim formu, bildirim özeti (`php artisan notifications:send instant`).

## 3. Production `.env`

- [ ] `APP_ENV=production`, `APP_DEBUG=false`, `APP_URL=https://kadir.gulec.tr`
- [ ] `APP_KEY` üretildi (`php artisan key:generate`), yerelinkiyle aynı değil.
- [ ] `LOG_LEVEL=warning` (ya da `error`)
- [ ] `DB_*`: sunucuda MariaDB 11.4 var, `utf8mb4_tr_0900_ai_ci` orada yok: `DB_COLLATION=utf8mb4_uca1400_turkish_ai_ci`, veritabanı `utf8mb4` / aynı sıralama.
- [ ] `TURNSTILE_SITE_KEY` / `TURNSTILE_SECRET_KEY`: **gerçek** anahtarlar (yereldeki `1x000…` test anahtarları her şeyi geçirir), Cloudflare'de alan adı `kadir.gulec.tr`.
- [ ] `TMDB_API_TOKEN`
- [ ] `QUEUE_CONNECTION=database`
- [ ] `ADMIN_STRONG_LOGIN` boş bırakılabilir (production'da kendiliğinden açık).

## 4. Sunucu

- [ ] PHP 8.3+, eklentiler: `gd` (WebP ve FreeType desteğiyle), `zip`, `pdo_mysql`, `mbstring`, `intl`.
- [ ] `mysqldump` ve `mysql` istemcileri kurulu (yedekleme ve geri yükleme bunları çağırır).
- [ ] Yükleme sınırları yedek arşivleri için yeterli: PHP `upload_max_filesize` / `post_max_size` ve web sunucusu (`client_max_body_size`) en az 100 MB.
- [ ] HTTPS sertifikası, `http` → `https` yönlendirmesi.
- [ ] Cron (Hestia'da): `* * * * * cd ~/web/kadir.gulec.tr/public_html && php8.4 artisan schedule:run >> /dev/null 2>&1`. Bildirimler ve duyurular bununla çalışır; sunucuda Supervisor olmadığı için kuyruk da her dakika buradan boşaltılır (`routes/console.php`, yedek oluşturma bunu bekler).
- [ ] `storage/` ve `bootstrap/cache/` web sunucusu kullanıcısına yazılabilir.

## 5. İlk kurulum komutları

- [ ] `admin` dalı `master`'a birleştirildi, CI yeşil. İlk otomatik deploy kodu yükler, `migrate`, `permissions:sync`, `storage:link` ve `optimize`'ı çalıştırır (`deployment/remote.sh`).
- [ ] `php8.4 artisan key:generate`, ardından `php8.4 artisan optimize`
- [ ] `php artisan db:seed --class=RealContentSeeder --force` (bir kere; `DemoSeeder` production'da **çalıştırılmaz**)
- [ ] `php artisan user:create-admin`, ardından ilk girişte 2FA ya da passkey kur (production'da admin paneli bunu ister).
- [ ] `php artisan optimize` (config, route, view, event önbellekleri)

## 6. Yayından sonra

- [ ] `/admin/yedekler`'den bir yedek oluştur ve indir (kuyruk çalışıyor mu?).
- [ ] `/sitemap.xml`, `/robots.txt`, `/yazilar/rss` açılıyor; bir yazının bağlantısı paylaşıldığında OG görseli görünüyor.
- [ ] Taslak bir yazı ziyaretçiye 404 veriyor; `/stil` ve `/admin/stil` production'da 404 veriyor.
- [ ] Kayıt ve iletişim formunda Turnstile kutusu görünüyor ve gönderim çalışıyor.

## Her deploy'da

`master`'a her push'ta `.github/workflows/deploy.yml` testleri çalıştırır; geçerse composer (`--no-dev`) ve `npm run build` CI'da yapılır, dosyalar `rsync` ile `~/web/kadir.gulec.tr/public_html`'e gider (`.env`, `storage/` ve `public/storage` dokunulmaz), sonra sunucuda `deployment/remote.sh`: `down` → `migrate --force` → `permissions:sync` → `optimize` → `queue:restart` → `up`. Bir adım hata verirse site yine açılır, workflow kırmızı olur. Elle de çalıştırılabilir (Actions → CI/CD → Run workflow).

- [ ] Büyük bir değişiklikten önce `/admin/yedekler`'den yedek al.
