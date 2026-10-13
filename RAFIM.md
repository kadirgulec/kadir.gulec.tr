# Rafım Planı: kadir.gulec.tr

Bu dosya, "Rafım" bölümünün (izlediklerim + okuduklarım) ve "Öğrendiklerim → Panom" geçişinin kararlarını, adımlarını ve uygulama notlarını tutar. Tasarım kararları `TASARIM.md`, admin ve üyelik kararları `ADMIN.md`, notların kararları `OGRENDIKLERIM.md`, aylık değerlendirme `DEGERLENDIRME.md` içinde. Yeni bir oturumda önce bu dosyayı oku, sonra işaretlenmemiş ilk adımdan devam et.

> Kararlar 2026-10-13 tarihli soru-cevap oturumunda alındı. Bütün adımlar `rafim` dalında, her adım ayrı commit. Görsel adımlarda (2 ve 5) Kadir'in onayı beklenir; Adım 2 onaylanmadan veri modeline geçilmez.

---

## 1. Fikir

Sitede kitap verisi var ama sadece sayı olarak: Hakkımda'da "Bu yılki kitap hedefi: 14 / 20" görünüyor, hangi kitapların okunduğu görünmüyor. Okuduklarım bu boşluğu doldurur. Film, dizi ve kitap aynı yerde, **Rafım** sekmesi altında durur. Mobil çubuk beş sekmeyle sınırlı olduğu için Öğrendiklerim de sekmede **Panom** adını alır; iki isim aynı aileden: odadaki fiziksel nesneler, iyelik ekiyle.

Kadir kitapları çoğunlukla **e-kitap** olarak (Kindle) okuyor. Bu yüzden sayfa sayısı yerine yüzde kullanılır, sayfa toplamı gösterilmez.

## 2. Navigasyon ve isimler

- **Mobil alt çubuk (5 sütun):** Yazılar · Panom · Rafım · Hedefler · Projeler.
- **Masaüstü yan sekmeler:** Ana Sayfa · Yazılar · Panom · Rafım · Hedefler · Projeler · Hakkımda (sayı değişmez, yedi).
- **Panom (eski Öğrendiklerim):**
  - Sekme etiketi "Panom". Adresler değişmez: `/ogrendiklerim`, `/ogrendiklerim/{id}`, `/ogrendiklerim/rss` (RSS aboneleri ve dizinlenmiş sayfalar bozulmaz).
  - Başlık "Küçük notlar, büyük birikim" kalır, altına "bugün öğrendiklerim" satırı gelir. `<title>`: "Panom · öğrendiklerim".
  - Panoya sadece öğrendiklerim girer (alıntı, link vb. yok).
  - Sekme ikonu (köşesi kıvrık post-it) ve petrol rengi kalır.
- **"Pano" çakışması:**
  - Admin ana sayfası "Pano" → **"Masa"**: kenar çubuğu (`layouts/admin.blade.php`), sayfa başlığı (`⚡dashboard.blade.php`), zincir hatırlatma e-postası ("Panoda işaretle" → "Masada işaretle", HTML ve metin sürümü). Plan dosyalarındaki eski "admin panosu" ifadeleri tarihî kayıt olarak kalır.
  - Hedefler sayfasındaki "Panoda henüz bir kart yok." → "Henüz bir kart yok."
  - Admin'de notların "panoya yapıştı" mesajları kalır (sitedeki Panom'u kastediyor, doğru).
- **Admin menüsü** içerik türü adlarını kullanır: Öğrendiklerim, İzlediklerim, Okuduklarım. "Panom" ve "Rafım" sadece sitede.

## 3. Rafım

- **Bölüm:** `Section::Watched` → `Section::Shelf` (değer `shelf`, etiket "Rafım", route `shelf.index`). Veritabanında bölüm değeri saklanmıyor (`page_views` sadece path tutuyor); değişiklik kod, CSS (`data-section`), OG paleti, sitemap ve testlerle sınırlı. `--color-watched` CSS token'ı `Tag::tapeColor()` paletinde de kullanılıyor, yeniden adlandırılırsa orası da güncellenir.
- **Renk:** İzlediklerim'in pembe-mercanı. Detay sayfalarında film afişten, kitap kapaktan kendi vurgu rengini alır (`PosterPalette`).
- **İkon:** Rafta iki kitap sırtı ve bir film makarası.
- **Adresler:** `/rafim` (ortak sayfa). İzlediklerim'in adresleri aynen kalır (`/izlediklerim`, `/izlediklerim/{film|dizi}/{slug}`). Kitaplar: `/okuduklarim`, `/okuduklarim/{slug}`.
- **Alt sekmeler:** `/rafim`, `/izlediklerim` ve `/okuduklarim` sayfalarının üstünde washi tape görünümünde "Hepsi · İzlediklerim · Okuduklarım".
- **`/rafim` ortak sayfa:**
  - "Şu an okuyorum" ve "şu an izliyorum" şeritleri.
  - Tarihe göre gruplanmış **karışık günlük**: film/dizi izlemeleri (`Viewing`), bitirilen ve yarım bırakılan kitaplar. Kitaba başlamak günlüğe girmez, sadece "şu an okuyorum"da görünür.
  - İzleyeceklerim ve okuyacaklarım: iki ayrı kısa liste, her birinden ilk 3 ve tamamına link. Tek karışık "sırada" listesi yok.

## 4. Okuduklarım

### Kitap

- **Kapsam:** Sadece kitap (makale yok).
- **Alanlar:** başlık, orijinal başlık (çeviriyse), yazar(lar), çevirmen, yayınevi, yıl, ISBN, okunduğu dil, biçim (basılı / e-kitap / sesli; varsayılan **e-kitap**), özet, kapak (`ImageStore`, yerelde) ve kapak renkleri, puan, favori, yorum, okuyacaklarım sırası ve notu, meta açıklama. **Sayfa sayısı alanı yok.**
- **Puan:** 10 üzerinden, yarım puanlı, kırmızı kalem dairesi; ayrıca el çizimi favori yıldızı (İzlediklerim'in bileşenleri). Yarım bırakılan kitaba da isteğe bağlı puan verilebilir.
- **Yorum:** Markdown (`RendersMarkdown`), spoiler blokları, yeni `:::alıntı Kişi … :::` bloğu, yorumun kendi yayın zamanı (`review_published_at`, filmdeki gibi).
- **Okuyacaklarım:** Sıralı liste (`Sortable`), "kimin önerisi" notu; okuma başlayınca listeden çıkar.
- **Adres:** Slug başlıktan; çakışırsa `baslik-yazar`. `HasSlugRedirects` ile eski adresler yönlenir.

### Okuma

- Her okuma ayrı bir kayıt (filmdeki `Viewing` gibi); tekrar okuma ayrıca saklanmaz, öncesinde başka okuması olan okumadır.
- **Durumlar:** okuyorum / ara verdim / bitirdim / yarım bıraktım.
- **Tarihler:** başlangıç ve bitiş (bitirdim / yarım bıraktım).
- **İlerleme:** Her zaman **yüzde**. Basılı kitapta formda "143/671" yazılırsa yüzdeye çevrilir.

### Kitap bilgisinin kaynağı

- **Open Library** (anahtar gerekmez): admin'de ISBN ya da başlıkla arama, seçilen kaydın bilgileri ve kapağı içe aktarılır. Her alan elle düzeltilebilir, kapak elle yüklenebilir. Site sayfaları Open Library'ye hiç istek atmaz (TMDB'deki gibi).
- 2026-10-13 denemesi: "Kürk Mantolu Madonna" iyi geldi; "Tutunamayanlar" üç ayrı kayıt, yazar adı üç farklı yazımla ("Oguz", "Oğuz", "Oǧuz"), sayfa/kapak her kayıtta yok. Türkçe kitaplarda elle düzeltme sık olacak; ISBN araması en isabetlisi.
- **Kapsam dışı:** Google Books, barkod okutma, geçmiş okumaların içe aktarılması, Kindle'dan (`My Clippings.txt`) alıntı aktarma.

### Sayfalar

- **Liste (`/okuduklarim`):** Üstte "Son okuduklarım" kapak şeridi (İzlediklerim'in afiş şeridi gibi), "şu an okuyorum", yıllara göre gruplanmış günlük. "2026: 14 kitap" satırı; o yılın kitap hedefi varsa "14 / 20" ve hedefe link. Filtre yok (yazar, dil, puan filtreleri sonraya).
- **Detay (`/okuduklarim/{slug}`):** İzlediklerim'in şablonu. Polaroid yerine bantla yapıştırılmış kapak, yanında el yazısı bilgiler (yazar, çevirmen, dil, biçim, okuma tarihleri). "Okuyorum" durumunda kapaktan sarkan **kitap ayracı şeridi** yüzdeyi gösterir. Puan dairesi, favori yıldızı, yorum, alıntı koleksiyonu, "bu kitaptan notlarım". Yorumsuz kitapta yorum yerine özet.
- **Yorum yok** (İzlediklerim'le tutarlı). Rafım için ayrı RSS yok.

## 5. Alıntılar (kitap ve film/dizi ortak)

- **Ortak yapı:** Polimorfik alıntı kaydı (kitap ya da `Watchable`): metin, kim söyledi (isteğe bağlı), yer (isteğe bağlı serbest metin: kitapta "%34", "3. bölüm", "s. 143"; dizide "S2 · B5"), yayın durumu, sıra.
- **Görünüm:** Detay sayfasında post-it koleksiyonu. Panom'a ya da başka bir yere gitmez.
- **Yorum içi bloklar kalır:** Filmlerde `:::replik Kişi`, kitaplarda `:::alıntı Kişi`. Yorum içindeki alıntı metnin akışında durur; ayrı kayıtlar yorumdan bağımsız birikir (yorumu olmayan filmin/kitabın da alıntısı olabilir).
- **Editör kılavuzu (ⓘ):** `components/admin/markdown.blade.php` kılavuzu genişler: `:::alıntı` bloğu ve iki yolun farkı ("Yorumun akışında geçen alıntıyı burada blokla yaz. Yorumdan bağımsız biriktirdiklerini aşağıdaki Alıntılar bölümüne ekle.").

## 6. Siteye bağlantılar

### Kitap hedefi

- Hedefi sistem oluşturmaz, Kadir oluşturur. Yıllık sayısal hedeflere **"Okuduklarım'dan sayılsın"** anahtarı eklenir. `GoalContent::readingGoal()` birim adı ("kitap") yerine bu anahtara bakar.
- Bir okuma "bitirdim" olunca, **bitiş tarihinin yılına ait** ve anahtarı açık hedefe `GoalProgress` yazılır: "+1 kitap: Başlık". Kayıt okumaya bağlıdır (`reading_id`): durum geri alınırsa silinir, bitiş tarihi başka yıla taşınırsa taşınır.
- Anahtar yılın ortasında açılırsa o yıl daha önce bitirilen kitaplar geriye dönük sayılır.
- Tekrar okuma sayılır, yarım bırakılan sayılmaz.
- Anahtarı açık hedeflerde Masa'daki "+1" düğmesi (`⚡dashboard.blade.php`, `addOne()`) kalkar, yerine "Okuduklarım'dan sayılıyor" yazar (çift sayım olmasın). Hedef düzenleme formunda elle düzeltme kalır.
- **Yılbaşı:** Ocak'ta Masa'nın "Bugün" kartında "2027 için kitap hedefin yok, geçen yılki (20) kopyalansın mı?" önerisi, tek tık.
- Mevcut `GoalProgress::created` duyuruları (`Announcements`) otomatik kayıtlarda da çalışır.

### Diğer bağlantılar

- **Aylık değerlendirme:** `MonthlyReviewStats` bitirilen kitap sayısını sayar, `ReviewSuggestions` buna göre öneri üretir.
- **Ana sayfa:** "şu an izliyorum" kartının yanına "şu an okuyorum" kartı.
- **Hakkımda:** "Şu an" listesine okuduğum kitap satırı.
- **Notun kaynağı:** Notta isteğe bağlı, en fazla bir kaynak (kitap ya da film/dizi; polimorfik). Kitap/film sayfasında "bu kitaptan notlarım", not sayfasında ve post-it'te "kaynak: …". Bugün notlar yazılara sadece ortak etiketle bağlanıyor (`NoteContent::forPost()`); bu ondan bağımsız, ek bir bağ.
- **Bildirim ve takip:** Kitap yorumu yayınlanınca duyuru (filmdeki gibi). "Bitirdi" haberi notlardaki gibi sadece özete girer (`digestOnly`). Kitap sayfasında takip düğmesi (`HasFollowers`).
- **SEO:** JSON-LD `Book` + `Review` (`StructuredData`), kapaklı OG görseli (`og/kitap/{slug}.png`), sitemap'te yayındaki kitaplar ve `/rafim`.

## 7. Admin

- **İzin:** Yeni `Permission::ManageBooks` ("Okuduklarımı yönetir"). `ManageWatched`'a dokunulmaz, kayıtlı roller değişmez.
- **Kenar çubuğu:** İçerik → İzlediklerim'in yanında Okuduklarım.
- **Ekranlar:** liste (arama, durum filtresi), ekle (Open Library araması + elle ekleme), düzenle (bilgiler, kapak, okumalar, alıntılar, yorum), okuyacaklarım (sürükle-bırak sıralama). İzlediklerim'in ekranları örnek alınır.
- **Film/dizi düzenleme ekranına** alıntılar bölümü eklenir.
- **Masa'da "Okuyorum" kartı:** okunan her kitap için yüzdeyi hızlı güncelleme, alıntı ekleme, "bitirdim".
- **PWA kısayolu:** "Kitap ekle" → ekleme ekranı (`ManageBooks` olana).
- **Yedekleme ve veri:** yeni tablolar yedek manifest'teki içerik sayılarına eklenir. Morph map'e `book`, `reading`, `quote` girer.
- **Demo veriler:** `DemoSeeder` örnek kitaplar, okumalar ve alıntılar ekler.

## 8. Adımlar

- [x] **Adım 1: Panom ve Masa** (sekme etiketi "Panom", alt başlık ve `<title>`; admin "Pano" → "Masa", zincir e-postası; Hedefler'deki boş pano cümlesi; testler). Tek başına yayına alınabilir.
- [x] **Adım 2: Prototip** (demo verilerle `/rafim` ortak sayfa, `/okuduklarim` liste, kitap detayı, alt sekmeler, Rafım ikonu, mobil çubuk; `Section::Shelf`). *Kadir'in onayı beklenir; onaysız Adım 3'e geçilmez.*
- [ ] **Adım 3: Model** (kitap, okuma, alıntı tabloları ve modelleri, factory'ler, durum enum'u, morph map, `ManageBooks`, yedek sayımı, demo veriler).
- [ ] **Adım 4: Admin** (liste, ekle, düzenle, okuyacaklarım, Open Library istemcisi ve kapak indirme, film/dizi alıntıları, ⓘ kılavuz, Masa'daki "Okuyorum" kartı, PWA kısayolu).
- [ ] **Adım 5: Ziyaretçi sayfaları** (prototipin gerçek veriye bağlanması: `/rafim`, `/okuduklarim`, detay, alıntı koleksiyonu, film sayfasında alıntılar, ana sayfa ve Hakkımda). *Kadir'in onayı beklenir.*
- [ ] **Adım 6: Bağlantılar** (kitap hedefi anahtarı ve otomatik ilerleme, Masa'daki +1'in kalkması, yılbaşı önerisi, aylık değerlendirme, notun kaynağı).
- [ ] **Adım 7: Paylaşım ve bildirimler** (title/meta, JSON-LD, OG görseli, sitemap, yorum duyurusu, özete giren "bitirdi" haberi, takip).

## 9. Uygulama notları

*(Her adımda buraya eklenir.)*

- **Panom ve Masa (1):** `Section::Notes` etiketi "Panom" (yan sekme, mobil çubuk, JSON-LD breadcrumb hepsi `label()`'dan okur); değer `notes` ve adresler aynı. Pano başlığı `<title>` "Panom · öğrendiklerim" (etiketle "#etiket · Panom · öğrendiklerim"), not sayfasında "← Panom", manifest kısayolu "Panom". Başlık altındaki mevcut el yazısı satır ("yol üstünde öğrendiğim küçük şeyler") alt satır işini görüyor, ikinci satır eklenmedi (Kadir onayladı). İçeriğin adı olarak "Öğrendiklerim" kalanlar: RSS başlığı, not OG görselinin üst yazısı, hesap ayarlarındaki bildirim cümlesi, admin menüsü. Admin ana sayfası "Masa" (kenar çubuğu, başlık, zincir e-postası "Masada işaretle"). Hedefler'de "Henüz bir kart yok."
- **Prototip (2):** `Section::Watched` → `Section::Shelf` (değer `shelf`, etiket "Rafım", route `shelf.index`); renkler `--color-watched` token'larını kullanmaya devam ediyor (`[data-section='shelf']`), token yeniden adlandırılmadı. Manifest kısayolu ve hata sayfası linkleri artık Rafım'a gidiyor. Demo veri `BookContent` içinde statik (9 kitap, 5 okuyacak; `BookEntry` dizi şekli), Adım 3'te içi veritabanına bağlanacak, dizi şekli aynı kalacak. Yeni enum'lar `ReadingStatus` ve `BookFormat`. Bileşenler: `book-cover` (bantlı kapak, kapak resmi yoksa renklerden çizilir; başlık `cqw` ile kapak genişliğine ve en uzun kelimeye göre boyutlanır, kelime ortadan bölünmez; okunurken ayraç şeridi ve yüzde), `shelf-tabs`, `quote-note`, `diary-card` (+ `watched-diary-card`, `book-diary-card`), `watching-card`, `reading-card`, `to-read-entry`. Alıntı koleksiyonunun başlığı "Altını çizdiklerim". Günlük kartında kitabın üst satırı "Kitap · yazar" (Türkçe değilse "· dil"); biçim sadece detayda. `:::alıntı Kişi` bloğu `:::replik` ile aynı post-it. `/rafim` günlüğü aylara göre karışık, henüz sayfalama yok. "Bu kitaptan notlarım" prototipte boş (notun kaynağı Adım 6'da). Mobilde sütun sayısı verilmemiş grid'ler içeriğe göre genişleyip taşıyordu: kart listelerine `grid-cols-1`, `/rafim` bölümlerine `min-w-0` (İzlediklerim sayfasına da). Kadir onayladı.
