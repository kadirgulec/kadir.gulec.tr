# Tasarım Planı: kadir.gulec.tr

Bu dosya, tasarım kararlarını ve prototip adımlarını kayıt altında tutar. Yeni bir oturumda önce bu dosyayı oku, sonra işaretlenmemiş ilk adımdan devam et.

> **Kapsam:** Şimdilik sadece tasarım. Admin panel, veritabanı ve iş akışları daha sonra yapılacak. Prototip sabit örnek verilerle çalışır.

---

## 1. Genel kimlik

- **Dil:** Sadece Türkçe.
- **Kitle ve ton:** Kişisel bir "dijital bahçe" / hayat günlüğü. Kitle arkadaşlar, aile, Türk yazılımcı topluluğu ve meraklı ziyaretçiler. Ton samimi, sıcak ve oyunbaz. Profesyonel CV için kadir.guelec.eu'ya link verilir.
- **Görsel karakter:** Renkli ve oyunbaz, ama aynı zamanda "dijital karalama defteri" hissi veren bir site. Bütün bölümler aynı defterin farklı sayfaları gibi durmalı, hiçbir bölüm "başka bir siteye geldim" hissi vermemeli.
  - İzlediklerim, renkli ve oyunbaz uca daha yakın.
  - Yazılar, sıcak ve defter hissi veren uca daha yakın.
- **Logo:** kadir.guelec.eu'daki daire içinde "kg" logosu (`https://kadir.guelec.eu/assets/logo.svg`). Fotoğraf yerine kullanılır. Hafif eğik, mürekkep rengi bir **mühür / damga** gibi görünür.

## 2. Ortak omurga

- **Sabit öğeler:** Kâğıt/krem zemin, aynı font ailesi, aynı navigasyon, el çizimi detaylar (altı çizgiler, ok karalamaları, washi tape/bant), aynı kart köşe yuvarlaklığı ve gölgeler.
- **Bölüme göre değişenler:** Vurgu rengi ve renk yoğunluğu.
- **Navigasyon:** Defter sekme ayracı metaforu, ölçülü bir şekilde.
  - Masaüstü: Kenarda renkli sekmeler. Aktif bölümde sayfa kenar şeridi o bölümün rengini alır.
  - Mobil: Altta renkli bir sekme çubuğu.

### Renkler

| Sekme | Renk |
|---|---|
| Ana sayfa | Mürekkep laciverti (logo damgası da bu renkte) |
| Yazılar | Kiremit / terrakota |
| İzlediklerim | Canlı pembe-mercan + her filmin kendi afiş rengi |
| Hedefler | Fosforlu yeşil / limon |
| Projeler | Hardal sarısı / turuncu |
| Hakkımda | Lavanta |

- **Kâğıt:** Sıcak krem (yaklaşık `#FBF7EE`)
- **Metin:** Koyu kahve-siyah mürekkep (saf siyah değil)
- **Kırmızı kalem:** Puan daireleri ve düzeltmeler için. Bütün bölümlerde aynı kırmızı kullanılır (araç rengi, bölüm rengi değil).
- Doygunluk orta seviyede: "Basılmış mürekkep" gibi, neon gibi değil.

### Dark mode: "Gece defteri"

- Siyah sayfalı eskiz defteri hissi. Zemin sıcak kömür grisi-siyah (yaklaşık `#1C1A17`), metin kırık beyaz (beyaz jel kalem).
- Bölüm renkleri parlaklaşır (neon/metalik jel kalem gibi). Kırmızı puan dairesi parlak kırmızı jel kalem gibi görünür. Bant ve sticker'lar hafif soluklaşır.
- Varsayılan olarak sistem ayarını takip eder. Navigasyonda **masa lambası** metaforuyla manuel geçiş düğmesi bulunur. Tercih tarayıcıda hatırlanır.

### Tipografi (Türkçe karakter desteği şart)

| Rol | Font |
|---|---|
| Başlık | Fraunces |
| Gövde / arayüz | Nunito Sans |
| El yazısı (sadece kısa metinler: kenar notları, etiketler, puan, sticker) | Caveat |
| Monospace (tarihler, kod, küçük detaylar) | JetBrains Mono |

Fontlar prototipte alternatifleriyle karşılaştırıldı ve onaylandı.

### Animasyonlar

1. Sekme geçişi: Defter sayfası çevirir gibi hafif kayma.
2. Hover: Eğik afiş ve polaroidler düzleşir ve hafifçe kalkar.
3. El çizimi animasyonlar: Puan dairesi, altı çizgiler ve fosforlu vurgular kalemle çiziliyormuş gibi belirir.
4. Zincir halkaları sırayla takılır, "BAŞARILDI" damgası basılır.
5. Lamba: Dark mode geçişinde ışık lambadan yayılır gibi açılıp kapanır.

- Konfeti veya parıltı gibi sürprizler **yok**.
- Kurallar: Geçişler <400ms, el çizimi animasyonları (altı çizgi, daire) 500ms. Her animasyon sadece bir kere oynar, `prefers-reduced-motion` açıksa tamamen kapanır.

### Sekmeler ve URL'ler

| Sekme | URL |
|---|---|
| Ana Sayfa (sekme olarak "kg" logosu) | `/` |
| Yazılar | `/yazilar` |
| İzlediklerim | `/izlediklerim` |
| Hedefler | `/hedefler` |
| Projeler | `/projeler` |
| Hakkımda | `/hakkimda` |

Detay sayfaları: `/izlediklerim/film/{slug}`, `/izlediklerim/dizi/{slug}`, `/yazilar/{slug}`, `/hedefler/{slug}`, `/projeler/{slug}`. URL'lerde Türkçe karakter kullanılmaz.

---

## 3. Sayfalar

### Ana sayfa: Defterin kapağı / "şu sıralar" panosu

- Üstte kısa selamlama ve logo damgası.
- **Selamlama metni:** *"Yedi yıl hırsız kovaladım, şimdi hırsız beni kovalıyor."* Bu cümleye hiçbir açıklama, dipnot, kenar notu veya tooltip eklenmez.
- Altında her bölümden canlı bir parça, her kart kendi bölümünün renginde: son yazı, son izlenen film, "şu an izliyorum" dizileri, aktif zincir serisi ("🔥 23 gün"), öne çıkan proje.

### Hakkımda

1. **Giriş:** *"Ankara'da memurdum, Düren'de yazılımcıyım. Arada bir sürü şey oldu, burası o defter."* Büyük Fraunces başlık, bir kelimede el çizimi daire veya altı çizgi, yanında eğik "kg" damgası.
2. **Hikâyem:** El çizimi bir yol gibi zaman çizelgesi. Kişisel bir dille anlatılır. Prototipte yer tutucu metin kullanılır, gerçek metni Kadir yazar.
3. **Şu an ne yapıyorum:** "Now page" tarzında liste (İzlediklerim ve Projeler'den beslenebilir).
4. **Alet çantam:** Defter kapağına yapıştırılmış sticker koleksiyonu gibi teknoloji logoları.
5. **İletişim:** E-posta, GitHub, kadir.guelec.eu linki ("Almanca/İngilizce profesyonel CV için →").

### Yazılar (blog)

- **Liste:** Fihrist / içindekiler düzeni (tarih monospace, başlık, noktalı çizgi, okuma süresi). Görselsiz. Öne çıkan 1-2 yazı, kısa giriş paragrafıyla geniş "defter girdisi" olarak gösterilir.
- **Etiketler:** Renkli washi tape parçaları gibi görünür, liste üstünde filtre olarak kullanılır.
- **Yazı sayfası:**
  - Tek sütun, rahat satır uzunluğu, serif başlık, sans-serif gövde.
  - Kenar notları (sidenotes): Masaüstünde sağ kenarda el yazısı, mobilde tıklanınca açılır.
  - Fosforlu kalem vurgusu: Düzensiz kenarlı, sarı.
  - Kod blokları: Kâğıda yapıştırılmış koyu kart, köşede bant ve dil etiketi, kopyala butonu.
  - Yazı sonu: "kg" mührü, önceki/sonraki yazı, ilgili yazılar.
  - İçindekiler (TOC) **yok**.

### İzlediklerim (film ve dizi)

- **Liste:** Üstte "Son izlediklerim" büyük afiş şeridi, altında tarihe göre gruplanmış günlük ("Eylül 2026"). Yorumlu kayıtlar büyük kart, yorumsuzlar küçük kart olarak gösterilir. Ayrıca "Şu an izliyorum" şeridi.
- **Puan:** 10 üzerinden, yarım puanlı (örn. 7.5). "Öğretmen notu" tarzında, kırmızı kalemle çizilmiş daire içinde, kartın köşesinde hafif eğik durur.
- **Favori:** Ayrı bir işaret. El çizimi yıldız, her zaman aynı renk, puan dairesinin yanına karalanmış gibi.
- **Dizi durumu:** 📺 İzliyorum (ilerleme "S2 · B5", kurşun kalemle doldurulmuş çubuk) · ✅ Bitirdim · ⏸️ Ara verdim · 🪦 Bıraktım. Bölüm bazında takip yok.
- **Dizi puanı:** Genel puan bitince veya bırakınca verilir. Sezon puanı opsiyoneldir (detay sayfasında sezon listesinde, daha küçük daire ve kısa notla).
- **Detay sayfası (tek şablon):**
  - Afiş, bantla yapıştırılmış polaroid gibi hafif eğik. Yanında el yazısı bilgiler (yıl, yönetmen, nerede/ne zaman izlendi). Puan dairesi köşede.
  - Her filmin vurgu rengi afişinden çıkarılır (düzen aynı, renk filme özel).
  - **Yorumlu:** Çizgili kâğıt hissiyle yorum metni + spoiler blokları (siyah markörle kapatılmış, tıklayınca açılır) + replik kutuları (post-it).
  - **Yorumsuz:** Aynı şablon, yorum yerine özet, tür, yıl, oyuncular, puan ve izleme tarihi.

### Hedefler

Tek sayfa, yukarıdan aşağıya üç kat (zoom out). Hedefler opsiyonel olarak bir üst hedefe bağlanabilir, bu bağlantı küçük bir etiketle gösterilir ("↑ Kendi ürünüm").

1. **Zincirler (günlük alışkanlıklar):**
   - Kart: El çizimi halkalarla son 2-3 haftanın zinciri ve "🔥 23 gün" sayacı.
   - Detay: GitHub tarzı yıllık ızgara ve istatistikler (en uzun seri, toplam gün, başarı yüzdesi).
   - **Mazeretli gün:** Zinciri kırmaz. Bantla "yamanmış" halka olarak gösterilir.
2. **Bu yıl (yıllık hedefler):** Üç tip.
   - Sayısal: El çizimi ilerleme çubuğu ("7 / 12") ve ince bir "bugün" çizgisi (yolunda mı göstergesi).
   - Kilometre taşlı: Kontrol listesi, tamamlananların üstü çizili.
   - Evet/Hayır: Büyük kutucuk, tamamlanınca "BAŞARILDI" damgası.
   - **Geçmiş yıllar arşivi:** Tutmayan hedefler silinmez, üzerleri kurşun kalemle karalanır.
3. **Uzun vade:** Mantar panoya iğnelenmiş kartlar (başlık, "neden önemli", opsiyonel görsel). Tıklanınca hedef sayfası açılır: tarihli güncellemeler/notlar ve bu hedefe bağlı yıllık hedefler ile zincirler.

- **Görünürlük (admin panelden sonra ayarlanacak, tasarım şimdi):** Her hedef için üç seviye:
  - **Açık:** Normal görünür.
  - **Sansürlü:** Kart yerinde durur, başlık siyah markörle karalanmış, ilerleme görünür ("🔒 ██████ · 🔥 41 gün"). Spoiler bloğuyla aynı markör efekti.
  - **Gizli:** Ziyaretçiye hiç gösterilmez.

### Projeler

- **Liste:** Kartlar (ekran görüntüsü, isim, tek cümle açıklama, teknoloji etiketleri, demo/GitHub linkleri).
- **Detay (vaka çalışması):** Hangi problemi çözüyor, neden yapıldı, ekran görüntüleri, teknik kararlar, öğrenilenler, şu anki durum.
- **Devlog:** Tarihli geliştirme notları (uzun vadeli hedeflerdeki günlük bileşeninin aynısı).
- Ekran görüntüleri bantla yapıştırılmış polaroid / tarayıcı penceresi çerçevesinde durur.
- Durum damgası: **YAPIM AŞAMASINDA** (sarı) · **YAYINDA** (yeşil) · **ARŞİV** (gri).
- İlk proje: **CoMon** (sözleşme ve ev bütçesi yönetimi, sayaç takibi, çok kullanıcılı hane desteği; Laravel, Livewire, Alpine.js, MySQL; demo: comon.guelec.eu).
- Yıllık hedefler ilgili projeye link verebilir ("CoMon'u yayınla" → `/projeler/comon`).

---

## 4. Prototip yaklaşımı

- Doğrudan bu Laravel projesinde yapılır: Blade şablonları, Tailwind 4 tasarım token'ları, sabit örnek veriler. Veritabanı ve admin yok.
- Bileşenler (puan dairesi, favori yıldızı, zincir, damga, polaroid, washi tape, spoiler, post-it, sekmeler, lamba düğmesi…) ileride gerçek verilere bağlanırken aynen kullanılacak şekilde yazılır.
- Starter kit'teki giriş/dashboard kısmına dokunulmaz (ileride admin panel olacak).
- Adım adım ilerlenir: Her adımdan sonra Kadir bakar ve onaylar, sonra bir sonraki adıma geçilir.

## 5. Adımlar

- [x] **Adım 1: Ortak temel.** Renk token'ları (açık ve gece defteri), fontlar, kâğıt zemin, sekme navigasyonu (masaüstü kenar, mobil alt çubuk), masa lambası dark mode düğmesi, temel animasyon kuralları. Font karşılaştırma bölümü. *(Onaylandı. Bkz. `/stil`.)*
- [x] **Adım 2: Ana sayfa.** Kapak / "şu sıralar" panosu, logo damgası, selamlama. *(Onaylandı.)*
- [ ] **Adım 3: İzlediklerim.** Liste (afiş şeridi, şu an izliyorum, günlük) ve yorumlu detay sayfası (puan dairesi, favori yıldızı, spoiler, replik kutusu, afiş rengi).
- [ ] **Adım 4: Hedefler.** Üç katlı sayfa (zincir kartları, yıllık hedef tipleri, uzun vade panosu), sansürlü kart durumu.
- [ ] **Adım 5: Yazılar.** Fihrist listesi, öne çıkan girdiler, washi tape etiketleri, yazı sayfası (kenar notları, fosforlu kalem, kod blokları, mühür).
- [ ] **Adım 6: Projeler.** Liste ve CoMon detay sayfası (devlog, durum damgası).
- [ ] **Adım 7: Hakkımda.** Giriş, hikâye zaman çizelgesi, şu an, alet çantası, iletişim.
- [ ] **Adım 8: Kalan detay sayfaları.** Yorumsuz film detayı, dizi detayı (sezon listesi), zincir detayı (yıllık ızgara), uzun vadeli hedef sayfası.

## 6. Uygulama notları

- **Bölümler:** `app/Enums/Section.php` tek kaynak (etiket, route adı). CSS, `data-section` niteliği üzerinden bölüm rengini seçer (`bg-section`, `text-section-ink`, `text-section-on`).
- **CSS:** Site için ayrı giriş `resources/css/site.css` (admin'deki Flux stilleriyle karışmasın diye). JS: `resources/js/site.js` (lamba, el çizimi animasyonları).
- **Layout ve bileşenler:** `resources/views/layouts/site.blade.php`, `resources/views/components/site/*` (logo, section-icon, tabs, bottom-bar, lamp, scribble). Sayfalar: `resources/views/site/*`.
- **Fontlar:** `vite.config.js` içinde Bunny Fonts ile tanımlı; build sırasında indirilip kendi sunucumuzdan sunuluyor (ziyaretçi üçüncü taraf font sunucusuna bağlanmıyor). Türkçe için `latin-ext` alt kümesi şart. İlk ekranda görünen varyantlar önceden yükleniyor (preload); başlık ve el yazısı fontları `font-display: block`, gövde ve monospace `fallback` kullanıyor (yedek font titremesini önlemek için).
- **Örnek veriler:** `app/Support/PrototypeContent.php`. Her metot ileride gelecek sorgunun döndüreceği şekli taklit eder; gerçek modellere geçerken sadece bu sınıf değişir. Ana sayfa `HomeController` üzerinden bu verileri alır.
- **Ortak bileşenler (Adım 2):** `note` (bantlı kart), `poster` (polaroid / üretilmiş afiş), `grade` (kırmızı daire puan, Türkçe ondalık virgül), `favorite-star`, `pencil-progress`, `chain` (halkalar: tamam / kopuk / bantlı), `status-stamp`, `browser-frame`.
- **Türkçe dil bilgisi:** `app/Support/TurkishDate.php` ("30 Eylül'de" gibi ünlü uyumuna göre ekler).
- **Stil rehberi:** `/stil`, sadece production dışında kayıtlı.
- **Tema tercihi:** `localStorage` içinde `theme` anahtarı (`light` / `dark`). Kayıt yoksa sistem ayarı takip edilir.

## 7. Açık konular (sonraya)

- Film/dizi verilerinin kaynağı (ör. TMDB). TMDB kullanılırsa sitede atıf/logo gösterilmesi gerekir, bunun için footer'da yer ayrılmalı.
- Afişten renk çıkarma, kayıt sırasında bir kere hesaplanıp saklanacak.
- Admin panel: İçerik yönetimi, hedef görünürlük seviyeleri, zincir işaretleme.
- Hakkımda sayfasının gerçek metinleri Kadir tarafından yazılacak.
