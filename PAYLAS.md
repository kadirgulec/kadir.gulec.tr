# Paylaş Butonu: kadir.gulec.tr

Bu dosya, herkese açık sayfalara eklenen "paylaş" ikonunun kararlarını, durumunu ve açık sorularını tutar. Yeni bir oturumda (başka bir bilgisayarda) önce bu dosyayı oku.

> 2026-10-07. Kadir'in isteği: "tüm sayfalarda olsun, başlığın altında küçük bir ikon, daha fazla soru sormadan her şeyi yap". Bütün iş `paylas` dalında. Dal push edildi, **master'a henüz merge edilmedi**.

---

## 1. Sorun

Uygulama ana ekrana eklenip (PWA, `display: standalone`) açıldığında adres çubuğu olmuyor. Bir yazıyı paylaşmak isteyen kişi bağlantıya ulaşamıyor.

## 2. Çözüm (yapıldı)

- **Bileşen:** `resources/views/components/site/share-button.blade.php` (`<x-site.share-button />`). Küçük, yuvarlak bir ikon butonu (kutudan çıkan ok, bölüm ikonları gibi hafif titrek çizgi). Yanında, kopyalama sonrası "bağlantı kopyalandı ✓" yazan bir `role="status"` alanı var.
- **Davranış:** `resources/js/site.js` → `initShareButtons()`.
  - `navigator.share` varsa (telefonlar, yüklü uygulama, bazı masaüstü tarayıcılar) cihazın kendi paylaşım menüsü açılır: `{ title: document.title, url }`.
  - Menüyü kapatmak (`AbortError`) hata sayılmaz, hiçbir şey olmaz.
  - Paylaşım menüsü yoksa ya da başka bir hata olursa bağlantı panoya kopyalanır.
  - İkisi de yoksa (örneğin HTTPS olmayan bir adres) buton sayfadan kaldırılır.
  - JavaScript kapalıysa buton hiç görünmez (`hidden [:where(html.js)_&]:inline-flex`, spoiler butonundaki yöntemle aynı).
- **Paylaşılan adres:** Sayfanın kendi adresi (`url()->current()`, yani canonical). `?page=2`, `utm_*` gibi ekler atılır. **Tek istisna:** `?etiket=…` korunur, çünkü filtrelenmiş liste paylaşılmaya değer.
- **Bilerek eklenmeyenler:** Twitter/Facebook/WhatsApp butonları ve dış widget'lar. Takip kodu ve çerez demek (DSGVO), sayfanın görünümüne de uymuyor. Telefonun paylaşım menüsü bunların hepsini zaten sunuyor.

## 3. Hangi sayfada, nerede

| Sayfa | Yer |
| --- | --- |
| Ana sayfa, Hakkımda | Başlığın altındaki el yazısı satırın altında, sola dayalı |
| Yazılar, Öğrendiklerim (liste) | Abonelik butonunun yanında |
| Projeler, İzlediklerim, Hedefler (liste) | Alt başlık satırının sağında (mobilde alta kayar) |
| Yazı | Etiketlerin sonunda |
| Not | Post-it'in altında, sağda (notun başlığı yok) |
| Proje | Demo / GitHub / takip et satırında |
| Film/dizi | Başlığın (ve orijinal adının) hemen altında |
| Uzun vadeli hedef, zincir | "Takip et" butonunun yanında |

**Eklenmeyen sayfalar:** Künye, gizlilik, stil rehberi, hata sayfaları (403/404/419/429/500/503), abonelikten çıkma sayfası, giriş/hesap sayfaları ve admin. Bunların paylaşılmasının bir anlamı olmadığını düşündüm (bkz. soru 1).

## 4. Testler

- `tests/Feature/Components/ShareButtonTest.php`:
  - Her bölüm sayfası kendi adresini paylaşıyor.
  - Bir yazı kendi adresini paylaşıyor.
  - Etiket filtresi korunuyor; sayfa numarası ve takip parametreleri atılıyor.
- Site sayfalarının mevcut testleri geçti: Posts, Notes, Projects, Watched, Goals, Home, About, SiteSections, ErrorPages (89 test).
- **Tüm test paketi bu bilgisayarda çalıştırılmadı.** Merge'den önce `php artisan test --compact` çalıştırılmalı.
- Paylaşım menüsünün kendisi tarayıcıya ait, testle denenemez. **Telefonda elle denenmeli** (aşağıdaki kontrol listesi).

## 5. Telefonda kontrol listesi

- [ ] iPhone, Safari: ikon → paylaşım menüsü açılıyor, başlık ve link doğru.
- [ ] iPhone, ana ekrandaki uygulama: aynısı.
- [ ] Android, Chrome ve yüklü uygulama: aynısı.
- [ ] Menü kapatılınca hata ya da "kopyalandı" yazısı çıkmıyor.
- [ ] Masaüstü Firefox: tıklayınca "bağlantı kopyalandı ✓" çıkıyor, link panoda.
- [ ] Gece defterinde (karanlık mod) ikon okunuyor.
- [ ] WhatsApp/Telegram önizlemesinde OG görseli ve başlık geliyor (bu zaten vardı, sadece kontrol).

## 6. Açık sorular (Kadir'e)

1. **Künye / gizlilik / stil rehberi:** Bunlarda da ikon olsun mu? Şu an yok.
2. **Yazının sonu:** Başlığın altındaki ikona ek olarak, yazının sonunda imza damgasının yanına el yazısıyla "beğendiysen paylaş →" gibi bir çağrı olsun mu? Yazıyı bitiren okur paylaşmaya en yakın kişi.
3. **Paylaşım metni:** Şu an sadece başlık (`Başlık · Kadir Gülec`) ve link gidiyor. Yazılarda kısa açıklama (meta description) da `text` olarak eklensin mi? Bazı uygulamalar (WhatsApp) metni ve başlığı birleştirip tekrar gösteriyor, o yüzden şimdilik eklemedim.
4. **Masaüstünde görünüm:** Masaüstünde paylaşım menüsü çoğu zaman yok, ikon "linki kopyala" gibi çalışıyor. İkon masaüstünde farklı olsun mu (örneğin zincir/link ikonu) yoksa aynı kalsın mı?
5. **Liste sayfalarında yer:** Projeler/İzlediklerim/Hedefler listelerinde ikon alt başlığın sağında. Mobilde alt satıra kayıp sola dayanıyor; buton iç boşluğundan dolayı metinle birkaç piksel kayık. Yeterli mi?
6. **Merge:** Telefonda denendikten sonra `paylas` master'a merge edilip push edilsin mi?
