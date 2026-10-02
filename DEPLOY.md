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
- [ ] `DB_*`: MySQL 8, veritabanı `utf8mb4` / `utf8mb4_tr_0900_ai_ci`.
- [ ] `TURNSTILE_SITE_KEY` / `TURNSTILE_SECRET_KEY`: **gerçek** anahtarlar (yereldeki `1x000…` test anahtarları her şeyi geçirir), Cloudflare'de alan adı `kadir.gulec.tr`.
- [ ] `TMDB_API_TOKEN`
- [ ] `QUEUE_CONNECTION=database`
- [ ] `ADMIN_STRONG_LOGIN` boş bırakılabilir (production'da kendiliğinden açık).

## 4. Sunucu

- [ ] PHP 8.3+, eklentiler: `gd` (WebP ve FreeType desteğiyle), `zip`, `pdo_mysql`, `mbstring`, `intl`.
- [ ] `mysqldump` ve `mysql` istemcileri kurulu (yedekleme ve geri yükleme bunları çağırır).
- [ ] Yükleme sınırları yedek arşivleri için yeterli: PHP `upload_max_filesize` / `post_max_size` ve web sunucusu (`client_max_body_size`) en az 100 MB.
- [ ] HTTPS sertifikası, `http` → `https` yönlendirmesi.
- [ ] Kuyruk worker'ı Supervisor ile: `php artisan queue:work --tries=3` (yedek oluşturma bunu bekler).
- [ ] Cron: `* * * * * cd /yol && php artisan schedule:run >> /dev/null 2>&1` (bildirimler ve duyurular).
- [ ] `storage/` ve `bootstrap/cache/` web sunucusu kullanıcısına yazılabilir.

## 5. İlk kurulum komutları

- [ ] `admin` dalı `master`'a birleştirildi, CI yeşil.
- [ ] `composer install --no-dev --optimize-autoloader`
- [ ] `npm ci && npm run build`
- [ ] `php artisan migrate --force`
- [ ] `php artisan permissions:sync`
- [ ] `php artisan storage:link`
- [ ] `php artisan db:seed --class=RealContentSeeder --force` (bir kere; `DemoSeeder` production'da **çalıştırılmaz**)
- [ ] `php artisan user:create-admin`, ardından ilk girişte 2FA ya da passkey kur (production'da admin paneli bunu ister).
- [ ] `php artisan optimize` (config, route, view, event önbellekleri)

## 6. Yayından sonra

- [ ] `/admin/yedekler`'den bir yedek oluştur ve indir (kuyruk çalışıyor mu?).
- [ ] `/sitemap.xml`, `/robots.txt`, `/yazilar/rss` açılıyor; bir yazının bağlantısı paylaşıldığında OG görseli görünüyor.
- [ ] Taslak bir yazı ziyaretçiye 404 veriyor; `/admin/stil` production'da kapalı.
- [ ] Kayıt ve iletişim formunda Turnstile kutusu görünüyor ve gönderim çalışıyor.

## Her deploy'da

- [ ] Testler ve CI yeşil.
- [ ] `composer install --no-dev --optimize-autoloader` · `npm ci && npm run build`
- [ ] `php artisan down` → `php artisan migrate --force` → `php artisan permissions:sync` → `php artisan optimize` → `php artisan queue:restart` → `php artisan up`
- [ ] Büyük bir değişiklikten önce `/admin/yedekler`'den yedek al.
