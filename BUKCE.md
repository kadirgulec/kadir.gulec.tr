# Bükçe notları

"Öğrendiklerim" bölümündeki **Bükçe** serisinin kararları, taslakları ve fikirleri. Kararlar en üstte, taslaklar ve fikirler altta. Yeni bir Bükçe notu istendiğinde önce bu dosya okunur, kararlara uyulur, sonra Durum tablosu güncellenir.

## Kararlar

- **Ne:** Bükçe, sözü eğip bükerek söylemenin "dili". Seri, söylenenle kastedilenin ayrıldığı ifadeleri toplar.
- **Nerede:** "Öğrendiklerim" notları olarak. Etiket **Bükçe** (slug `bukce`). `/ogrendiklerim?etiket=bukce` kendiliğinden "Bükçe sözlüğü" sayfası olur.
- **Kaynak:** Fikir Sema Maraşlı'nın "Bükçe: Kadın Dili" öyküsünden geliyor (*Eşimle Tanışmayı Unutmuşuz*, [cocukaile.net](https://www.cocukaile.net/bukce-kadin-dili)). Kaynak sadece **giriş notunda** bir kez verilir.
- **Kopyalama yok:** Öyküdeki örnekler not olarak paylaşılmaz: "Hiçbir şeyim yok", "Fark etmez", kuru "Peki", "Böyle de iyisin", "Ağır mıyım?". Notlar Kadir'in kendi gözlemleri ve yaygın ifadelerdir.
- **Herkesin Bükçesi:** Seri "kadın dili" diye sunulmaz, cinsiyet genellemesi yapılmaz. Bükçe evde, işte, Türkçede ve Almancada konuşulur. Lehçeler:
  - **Ev Bükçesi:** aile, misafirlik, günlük hayat
  - **Türk Bükçesi:** Türkçeye ve Türk kültürüne özgü kalıplar
  - **Alman Bükçesi:** Almanca ifadeler, Alman iş ve günlük hayatı
  - **Müşteri Bükçesi:** müşteriden, iş talebinden gelen cümleler
  - **Yazılımcı Bükçesi:** yazılımcıların kendi kaçamak cümleleri
- **Kalıp:** Notların başlığı yok. Her not ifadenin kendisiyle başlar:
  1. `**"İfade."**`
  2. Hangi lehçe ya da kelime anlamı
  3. Bükçede gerçek anlamı
  4. İsteğe bağlı: tek cümlelik bir yorum, ipucu ya da kişisel an
- **Uzunluk:** Ham metin 250 karakterin altında (editördeki sayaç 250'den sonra turuncu olur).
- **Biçim:** Sadece satır içi biçimler: kalın, italik, link, `==fosforlu kalem==`. Bir notta en fazla bir fosforlu kalem.
- **Tek etiket:** Her notun tam olarak bir etiketi var. Bütün seri **Bükçe** etiketini taşır. Almanca notlar #Almanca sayfasında görünmez; bu bilerek kabul edildi.
- **Doğruluk:**
  - Almanca ifadelerin anlamı yaygın kullanıma dayanır. Kadir yayınlamadan önce kendi deneyimiyle karşılaştırır.
  - Kişisel anı uydurulmaz. Kişisel cümleler Kadir'den gelir, taslakta yoksa boş bırakılır.
- **Ton:** Hafif ve esprili. Kimse küçümsenmez; Bükçe kusur değil, kibarlığın bir biçimi olarak anlatılır.
- **Yayın düzeni:**
  - Önce giriş notu, hemen ardından lehçeler notu. İkisi kalıbın dışında, `**"İfade."**` ile başlamaz.
  - Sonra birkaç günde bir not. Hepsi aynı gün paylaşılmaz.
  - Yaklaşık 20 not birikince hepsine bağlanan bir toplu yazı yazılır. Örnek: "Bükçe sözlüğü: 20 ifade, 2 dil".

## Durum

| # | İfade | Lehçe | Durum |
|---|---|---|---|
| 1 | Bükçe nedir? | giriş | taslak |
| 2 | Bükçenin lehçeleri | giriş | taslak |
| 3 | "Bakarız." | Türk | taslak |
| 4 | "Yok, tokum." | Ev / Türk | taslak |
| 5 | "Zahmet etmeyin." | Türk | taslak |
| 6 | "Interessant." | Alman | taslak |
| 7 | "Mal schauen." | Alman | taslak |
| 8 | "Das ist sportlich." | Alman | taslak |
| 9 | "Nicht schlecht." | Alman | taslak |
| 10 | "Küçük bir değişiklik." | Müşteri | taslak |
| 11 | "Bende çalışıyor." | Yazılımcı | taslak |

Durum değerleri: `fikir` → `taslak` → `yayında (tarih)`.

## Taslaklar

Her blok, not editörüne olduğu gibi yapıştırılır.

**1. Bükçe nedir?** (giriş, ilk yayınlanacak not)

```markdown
**Bükçe**, sözü eğip bükerek söylemenin dili. Adını Sema Maraşlı'nın [Bükçe: Kadın Dili](https://www.cocukaile.net/bukce-kadin-dili) öyküsünden aldım. Burada söylenenle kastedilenin ayrıldığı cümleleri topluyorum: evde, işte, Türkçede ve Almancada.
```

**2. Bükçenin lehçeleri** (giriş notundan hemen sonra yayınlanır)

```markdown
**Bükçenin lehçeleri.** Bükçe tek bir dil değil. Evde Ev Bükçesi, misafirlikte Türk Bükçesi, toplantıda Alman Bükçesi, taleplerde Müşteri Bükçesi, hata bildirimlerinde Yazılımcı Bükçesi konuşulur. ==Kuralı hep aynı: Asıl anlam, söylenmeyen kısımda.==
```

**3. "Bakarız."**

```markdown
**"Bakarız."** Türk Bükçesinin en esnek kelimesi. Ses tonuna göre "evet", "hayır" ya da "hiç düşünmedim" demek. ==Gerçek anlamını cümle değil, söyleyenin yüzü belli eder.==
```

**4. "Yok, tokum."**

```markdown
**"Yok, tokum."** Misafirlikte ilk ikram reddedilir. Bükçede bu "hayır" değil, "bir kez daha ısrar et" demek. Almanya'da ise ilk "Nein, danke" gerçekten hayırdır. Kimse ikinci kez sormaz, tabak boş kalır.
```

**5. "Zahmet etmeyin."**

```markdown
**"Zahmet etmeyin."** Kelime anlamı: uğraşmayın. Bükçede: "Uğraşmanızı takdir ederim ama istemiş gibi görünmeyeyim." Zahmet edildiğinde itiraz eden pek olmaz.
```

**6. "Interessant."**

```markdown
**"Interessant."** Alman Bükçesi. Bir fikre "interessant" denmesi her zaman iltifat değil. Ses tonu düzse anlamı çoğu zaman "ikna olmadım". Gerçekten beğenen Alman genelde nedenini de söyler.
```

**7. "Mal schauen."**

```markdown
**"Mal schauen."** Kelime anlamı: "Bir bakalım." Alman Bükçesinde: büyük ihtimalle hayır. Türkçede karşılığı hazır: "Bakarız."
```

**8. "Das ist sportlich."**

```markdown
**"Das ist sportlich."** Bir Alman takvime "sportlich" diyorsa bu övgü değil. Anlamı: "Bu süre çok iddialı, yetişmeyebilir." ==Toplantıda bunu duyunca tarihi yeniden konuşma zamanı gelmiştir.==
```

**9. "Nicht schlecht."**

```markdown
**"Nicht schlecht."** Ters yönde Bükçe. Kelime anlamı "fena değil" ama bir Alman'ın ağzından çıktığında çoğu zaman gerçek bir övgü. Almanlar övgüyü de abartmadan söyler.
```

**10. "Küçük bir değişiklik."**

```markdown
**"Küçük bir değişiklik."** Müşteri Bükçesi. Ekranda küçük, arka planda çoğu zaman büyük: Bir butonun yeri değil, bir iş kuralı değişiyor olabilir. İlk sorum hep şu: "Bu değişiklik neyi değiştirsin?"
```

**11. "Bende çalışıyor."**

```markdown
**"Bende çalışıyor."** Yazılımcı Bükçesi. Kelime anlamı: hata yok. Bükçede: "Senin ortamın benimkinden farklı ve nedenini henüz bilmiyorum." ==Asıl iş bu cümleden sonra başlar.==
```

## Fikirler

Henüz taslağı yazılmamış ifadeler. Taslak yazılınca Durum tablosuna taşınır.

- **"Eigentlich ja."** (Alman) "Aslında evet", yani büyük ihtimalle hayır. "Eigentlich" kelimesinden sonra genelde bir "aber" gelir.
- **"Hast du kurz Zeit?"** (Alman / Müşteri) "Kısa" hiçbir zaman kısa değildir.
- **"Passt."** (Alman) Tek kelimelik onay. Coşku beklenmez, "passt" yeterince iyi demek.
- **"Hallederiz."** (Türk) Söz mü, temenni mi? Bağlama göre değişir.
- **"Hızlıca bakabilir misin?"** (Müşteri) "Hızlıca" talebin büyüklüğünü değil, aciliyetini anlatır.
- **"Dün çalışıyordu."** (Müşteri) Yazılımcı Bükçesindeki "Bende çalışıyor"un ikizi.
- **"Kapıya kadar geldin, bir çay iç."** (Ev / Türk) Bir davetten çok bir zorunluluk.
