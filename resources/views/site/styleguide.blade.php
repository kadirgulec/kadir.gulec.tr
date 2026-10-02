@use('App\Enums\Section')

@php
    $pangram = 'Pijamalı hasta yağız şoföre çabucak güvendi.';
    $turkishLetters = 'İ ı Ğ ğ Ş ş Ç ç Ö ö Ü ü';

    $paperTokens = [
        ['name' => 'Masa', 'class' => 'bg-desk'],
        ['name' => 'Kâğıt', 'class' => 'bg-paper'],
        ['name' => 'Koyu kâğıt', 'class' => 'bg-paper-deep'],
        ['name' => 'Çizgi', 'class' => 'bg-rule'],
        ['name' => 'Mürekkep', 'class' => 'bg-ink'],
        ['name' => 'Soluk mürekkep', 'class' => 'bg-ink-soft'],
        ['name' => 'Kırmızı kalem', 'class' => 'bg-pen-red'],
        ['name' => 'Fosforlu kalem', 'class' => 'bg-highlighter'],
    ];

    $fontRoles = [
        ['role' => 'Başlık', 'name' => 'Fraunces', 'class' => 'font-display text-4xl font-extrabold tracking-tight', 'sample' => 'Zinciri kırma!'],
        ['role' => 'Gövde', 'name' => 'Nunito Sans', 'class' => 'font-sans text-lg', 'sample' => 'Yedi yıl hırsız kovaladım, şimdi hırsız beni kovalıyor. Bu defterde izlediklerimi, hedeflerimi ve yaptıklarımı tutuyorum.'],
        ['role' => 'El yazısı', 'name' => 'Caveat', 'class' => 'font-hand text-3xl font-bold', 'sample' => 'tekrar izlerim! → 8.5'],
        ['role' => 'Monospace', 'name' => 'JetBrains Mono', 'class' => 'font-mono text-base', 'sample' => '02.10.2026 · S2 · B5 · php artisan serve'],
    ];
@endphp

<x-layouts::site :section="$section" title="Stil Rehberi">
    <p class="font-mono text-xs tracking-widest text-ink-faint uppercase">Adım 1 · sadece geliştirme ortamında</p>

    <h1 class="relative mt-3 inline-block font-display text-5xl font-extrabold tracking-tight sm:text-6xl">
        Stil Rehberi
        <x-site.scribble variant="double" class="absolute -bottom-4 left-0 h-4 w-full text-section" />
    </h1>

    <p class="mt-10 max-w-2xl text-lg text-ink-soft">
        Defterin ortak malzemeleri: renkler, fontlar ve el çizimi öğeler. Sağ üstteki
        <span class="marker text-ink">masa lambasına</span> tıklayarak gece defterini dene.
    </p>

    {{-- Colors --}}
    <section class="mt-16" aria-labelledby="renkler">
        <h2 id="renkler" class="font-display text-3xl font-semibold">Renkler</h2>
        <p class="mt-1 font-hand text-2xl text-ink-soft">kâğıt, mürekkep ve iki kalem</p>

        <ul class="mt-6 grid grid-cols-2 gap-4 sm:grid-cols-4">
            @foreach ($paperTokens as $token)
                <li class="flex items-center gap-3">
                    <span class="{{ $token['class'] }} size-10 shrink-0 rounded-full border border-ink/15"></span>
                    <span class="text-sm">{{ $token['name'] }}</span>
                </li>
            @endforeach
        </ul>

        <p class="mt-10 font-hand text-2xl text-ink-soft">her sekmenin kendi rengi</p>

        <ul class="mt-4 grid gap-6 sm:grid-cols-2 lg:grid-cols-3">
            @foreach (Section::cases() as $item)
                <li data-section="{{ $item->value }}" class="relative rounded-sm bg-paper-deep p-5 pt-7">
                    <span class="tape -top-2.5 left-5 w-20 -rotate-2"></span>

                    <div class="flex items-center gap-3">
                        <span class="flex size-11 items-center justify-center rounded-full bg-section text-section-on">
                            <x-site.section-icon :section="$item" class="size-6" />
                        </span>
                        <div>
                            <p class="font-display text-xl font-semibold text-section-ink">{{ $item->label() }}</p>
                            <p class="font-mono text-xs text-ink-faint">/{{ ltrim(parse_url($item->url(), PHP_URL_PATH) ?? '', '/') }}</p>
                        </div>
                    </div>

                    <p class="mt-4 text-sm text-ink-soft">
                        Metin içinde <a href="{{ $item->url() }}" class="font-semibold text-section-ink underline decoration-section decoration-2 underline-offset-4">bir bağlantı</a>
                        ve <span class="rounded bg-section/20 px-1.5 py-0.5 font-mono text-xs text-section-ink">etiket</span>.
                    </p>
                </li>
            @endforeach
        </ul>
    </section>

    {{-- Typography --}}
    <section class="mt-20" aria-labelledby="tipografi">
        <h2 id="tipografi" class="font-display text-3xl font-semibold">Tipografi</h2>
        <p class="mt-1 font-hand text-2xl text-ink-soft">seçilen dört font</p>

        <dl class="mt-6 divide-y divide-dashed divide-rule">
            @foreach ($fontRoles as $font)
                <div class="grid gap-2 py-6 sm:grid-cols-[10rem_1fr] sm:gap-6">
                    <dt>
                        <span class="block font-mono text-xs tracking-wider text-ink-faint uppercase">{{ $font['role'] }}</span>
                        <span class="block text-sm font-semibold">{{ $font['name'] }}</span>
                    </dt>
                    <dd>
                        <p class="{{ $font['class'] }}">{{ $font['sample'] }}</p>
                        <p class="{{ $font['class'] }} mt-2 text-base! text-ink-soft">{{ $pangram }} · {{ $turkishLetters }}</p>
                    </dd>
                </div>
            @endforeach
        </dl>
    </section>

    {{-- Notebook elements --}}
    <section class="mt-20" aria-labelledby="ogeler">
        <h2 id="ogeler" class="font-display text-3xl font-semibold">Defter öğeleri</h2>
        <p class="mt-1 font-hand text-2xl text-ink-soft">her bölümde aynı, sadece rengi değişir</p>

        <div class="mt-8 grid gap-10 md:grid-cols-2">
            <div class="flex flex-col gap-8">
                <div>
                    <h3 class="font-mono text-xs tracking-wider text-ink-faint uppercase">Altı çizgi ve daire</h3>
                    <p class="mt-4 font-display text-3xl font-semibold">
                        <span class="relative inline-block">
                            Ankara'da
                            <x-site.scribble class="absolute -bottom-2 left-0 h-3 w-full text-section" />
                        </span>
                        memurdum,
                        <span class="relative inline-block px-1">
                            Düren'de
                            <x-site.scribble variant="circle" class="absolute -inset-x-3 -inset-y-2 h-[calc(100%+1rem)] w-[calc(100%+1.5rem)] text-pen-red" />
                        </span>
                        yazılımcıyım.
                    </p>
                </div>

                <div>
                    <h3 class="font-mono text-xs tracking-wider text-ink-faint uppercase">Fosforlu kalem</h3>
                    <p class="mt-4 max-w-md text-lg leading-relaxed">
                        Uzun bir paragrafın ortasında <span class="marker">önemli bir cümle satır sonunu aşsa bile vurgusu düzgünce devam eder</span>, sonra metin normal akar.
                    </p>
                </div>

                <div>
                    <h3 class="font-mono text-xs tracking-wider text-ink-faint uppercase">Kenar notu (el yazısı)</h3>
                    <p class="mt-4 flex items-start gap-3">
                        <span class="font-hand text-2xl leading-tight text-section-ink">← bunu sonra düzelt!</span>
                    </p>
                </div>
            </div>

            <div class="flex flex-col gap-10">
                <div>
                    <h3 class="font-mono text-xs tracking-wider text-ink-faint uppercase">Bantlı not kartı</h3>
                    <div class="relative mt-6 max-w-xs rotate-2 rounded-sm bg-paper-deep p-5 pt-7 shadow-[0_8px_20px_-10px_rgb(60_40_20/0.35)] transition-transform duration-200 hover:rotate-0 motion-reduce:transition-none dark:shadow-[0_8px_20px_-8px_rgb(0_0_0/0.8)]">
                        <span class="tape -top-3 -left-4 -rotate-12"></span>
                        <span class="tape -top-3 -right-4 rotate-12"></span>
                        <p class="font-hand text-2xl font-bold">Üzerine gel, kart düzleşsin.</p>
                    </div>
                </div>

                <div>
                    <h3 class="font-mono text-xs tracking-wider text-ink-faint uppercase">Logo damgası</h3>
                    <div class="mt-4 flex items-end gap-8">
                        <x-site.logo class="stamp size-28 -rotate-12 text-home-ink" />
                        <x-site.logo class="stamp size-16 rotate-6 text-pen-red" />
                    </div>
                </div>
            </div>
        </div>
    </section>

    {{-- Motion rules --}}
    <section class="mt-20" aria-labelledby="hareket">
        <h2 id="hareket" class="font-display text-3xl font-semibold">Hareket</h2>
        <ul class="mt-4 flex max-w-2xl list-disc flex-col gap-2 pl-5 text-ink-soft marker:text-section">
            <li>Sekmeler arası geçişte sayfa hafifçe kayar (Chrome, Edge, Safari 18+; diğer tarayıcılarda animasyonsuz geçiş).</li>
            <li>Lamba: Kapatınca ışık lambaya geri çekilir, açınca lambadan yayılır.</li>
            <li>El çizimi çizgiler ekrana girdiklerinde bir kere çizilir.</li>
            <li>Geçişler 400ms'nin altında, el çizimi çizgiler 500ms. İşletim sisteminde "hareketi azalt" açıksa hepsi kapalı.</li>
        </ul>
    </section>
</x-layouts::site>
