@use('App\Enums\Section')

{{--
    Privacy notice (Datenschutzerklärung) in Turkish. A DRAFT written by
    Claude: Kadir checks it with a generator or a lawyer before going live.
--}}
<x-layouts::site :section="Section::Home" title="Gizlilik">
    <article class="max-w-2xl">
        <p class="font-mono text-xs tracking-widest text-ink-faint uppercase">gizlilik · son güncelleme {{ config('legal.updated_at') }}</p>
        <h1 class="mt-3 font-display text-4xl font-extrabold tracking-tight sm:text-5xl">Gizlilik notu</h1>

        @unless (app()->isProduction())
            <p class="mt-6 rounded-sm bg-highlighter px-3 py-2 text-sm text-[#2b2420]">Taslak: yayından önce hukuki olarak kontrol edilecek.</p>
        @endunless

        <div class="prose-notebook mt-10">
            <p>Bu site kişisel bir defter. Ziyaret etmek için hiçbir bilgi vermen gerekmez. Üye olursan, yorum yazabilmen ve takip ettiklerin hakkında e-posta alabilmen için gereken en az bilgiyi saklarım. Reklam ve izleme aracı yok. Hangi sayfaların okunduğunu sadece çerezsiz ve kimliksiz bir sayımla görürüm (aşağıda).</p>

            <h2>Sorumlu</h2>
            <p>{{ config('legal.name') }}, {{ config('legal.city') }}, Almanya · <a href="mailto:{{ config('legal.email') }}">{{ config('legal.email') }}</a>. Ayrıntılar <a href="{{ route('imprint') }}">künyede</a>.</p>

            <h2>Ziyaret ettiğinde</h2>
            <ul>
                <li>Sunucu, isteği karşılamak için IP adresini ve tarayıcı bilgisini kısa süreli teknik kayıtlarda tutar (en fazla 14 gün), saldırıları ve hataları ayıklamak için.</li>
                <li>Fontlar, afişler ve bütün görseller bu sunucudan gelir; üçüncü taraf bir sunucuya bağlanmazsın.</li>
                <li>Açık/koyu tema tercihin sadece tarayıcında (<code>localStorage</code>) durur, bana gönderilmez.</li>
                <li>Site her ziyarette iki zorunlu çerez kullanır: bir oturum çerezi (<code>{{ config('session.cookie') }}</code>) ve formları sahte isteklere karşı koruyan bir CSRF çerezi (<code>XSRF-TOKEN</code>). İkisi de {{ config('session.lifetime') }} dakika hareketsizlikten sonra geçersiz olur. Oturumun kendisi sunucuda durur; oturum kaydında IP adresin ve tarayıcı bilgin de bulunur ve oturumun süresi dolunca silinir. Giriş yaparken "Beni hatırla"yı seçersen, her seferinde şifre sorulmasın diye bir hatırlama çerezi daha eklenir. Bu çerezlerin hepsi sitenin çalışması için gerekli (TDDDG md. 25/2), takip için kullanılmaz; bu yüzden çerez bandı yok.</li>
            </ul>

            <h2>Ziyaret istatistikleri</h2>
            <p>Hangi yazıların okunduğunu ve ziyaretçilerin nereden geldiğini görmek için sayfa görüntülemelerini kendi sunucumda sayarım. Bunun için çerez, JavaScript ya da üçüncü taraf bir hizmet kullanılmaz, tarayıcına hiçbir şey kaydedilmez. Her görüntülemede şunlar saklanır: açılan sayfanın adresi, seni bir bağlantıyla gönderen sitenin sadece alan adı (ör. <code>google.com</code>, arama terimleri olmadan), kaba tarayıcı, işletim sistemi ve cihaz türü (ör. "Firefox, Linux, masaüstü") ve zaman.</p>
            <p>Aynı kişiyi bir gün içinde iki kez saymamak için IP adresinden ve tarayıcı bilgisinden, her gün yenilenen rastgele bir anahtarla geri çevrilemez bir özet üretilir. IP adresi saklanmaz. Anahtar ertesi gün silindiği için bu özetler sana ya da önceki günlere bağlanamaz. Tarayıcın "Do Not Track" ya da "Global Privacy Control" sinyali gönderiyorsa hiç sayılmazsın. Kayıtlar {{ \App\Models\PageView::KEEP_MONTHS }} ay sonra kendiliğinden silinir. Hukuki dayanak: GVO md. 6/1 (f), hangi içeriklerin okunduğunu anlayıp siteyi ona göre geliştirmek.</p>

            <h2>İletişim formunu kullandığında</h2>
            <p>Yazdığın ad, e-posta adresi ve mesaj bu sitenin veritabanına kaydedilir ve bana e-posta olarak da gönderilir. Mesajı sadece ben okurum; cevap verebilmek ve konuşmamızın kaydı için tutarım. Sitedeki kayıt en geç 12 ay sonra kendiliğinden silinir, istersen daha önce de silerim. Mesajla birlikte IP adresin ya da hesabın saklanmaz; kötüye kullanımı önlemek için IP adresinden üretilen geri çevrilemez bir özetle en fazla bir gün süren bir sayaç tutulur. Hukuki dayanak: GVO md. 6/1 (f), mesajına cevap verebilmem.</p>

            <h2>Üye olduğunda</h2>
            <ul>
                <li><strong>Hesap:</strong> görünen ad, e-posta adresi, şifrenin geri çevrilemez özeti (hash); kullanırsan passkey'in açık anahtarı ve iki adımlı doğrulama anahtarı. E-posta adresin kimseye gösterilmez.</li>
                <li><strong>Yorumlar:</strong> yazdığın metin ve tarihi, adınla birlikte herkese açık. Yorumların için IP adresi saklanmaz; hız sınırı için kısa süreli bir sayaç tutulur.</li>
                <li><strong>Takipler ve bildirim tercihi:</strong> neyi takip ettiğin ve ne sıklıkla e-posta istediğin.</li>
            </ul>
            <p>Hukuki dayanak: hesabın ve istediğin bildirimler için GVO md. 6/1 (b), sitenin güvenliği ve spamın önlenmesi için GVO md. 6/1 (f).</p>

            <h2>Kullanılan hizmetler</h2>
            <ul>
                <li><strong>Cloudflare Turnstile</strong> (Cloudflare, Inc., ABD): sadece kayıt, yorum ve iletişim formlarında, robotları ayırmak için. Tarayıcın formu doldururken Cloudflare'e teknik bilgiler gönderir. Cloudflare AB–ABD Veri Gizliliği Çerçevesi'ne katılıyor. Dayanak: GVO md. 6/1 (f).</li>
                <li><strong>E-posta gönderimi:</strong> doğrulama, şifre yenileme, bildirim e-postaları ve iletişim formu mesajları bir e-posta servisi üzerinden gider; bu servise sadece e-posta adresin ve e-postanın içeriği iletilir. <em>(Servis seçilince adı buraya yazılacak.)</em></li>
                <li><strong>TMDB:</strong> film bilgileri ve afişler admin panelinden bir kere çekilip bu sunucuda saklanır. Sayfaları gezerken TMDB'ye bağlanmazsın.</li>
            </ul>

            <h2>Ne kadar saklıyorum?</h2>
            <p>Hesabın, sen silene kadar. İletişim formu mesajların en fazla 12 ay. Ziyaret istatistikleri {{ \App\Models\PageView::KEEP_MONTHS }} ay. Hesabını silince ad, e-posta, takiplerin ve tercihlerin silinir; yorumların konuşmalar bozulmasın diye "silinmiş üye" adıyla, sana bağlanamayacak şekilde kalır. Teknik kayıtlar en fazla 14 gün tutulur.</p>

            <h2>Hakların</h2>
            <p>Verilerine erişme, düzeltme, silme, işlenmesini kısıtlama ve itiraz etme hakkın var. Çoğunu kendin yapabilirsin: <a href="{{ route('profile.edit') }}">hesabım</a> sayfasından adını ve e-postanı değiştirir, verilerini JSON olarak indirir ya da hesabını silebilirsin. Geri kalanı için bana yaz. Ayrıca bir veri koruma denetim makamına şikâyet edebilirsin (Kuzey Ren-Vestfalya için: Landesbeauftragte für Datenschutz und Informationsfreiheit NRW).</p>
        </div>
    </article>
</x-layouts::site>
