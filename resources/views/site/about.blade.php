@use('App\Enums\Section')

@php
    // Light colors only: the sticker text is always dark ink.
    $stickerColors = ['var(--color-posts)', 'var(--color-goals)', 'var(--color-about)', 'var(--color-projects)', 'var(--color-watched)', '#8fb8de'];
    $stickerTilts = [-4, 3, -2, 5, -3, 2, -5, 4];
@endphp

<x-layouts::site :section="Section::About" title="Hakkımda" description="Ankara'da memurdum, Düren'de yazılımcıyım. Burası benim karalama defterim: hedef koyup kendimi motive ettiğim, yarınki beni daha iyi yapmaya çalıştığım yer.">
    {{-- Intro --}}
    <section class="grid items-start gap-8 md:grid-cols-[1fr_auto]">
        <div>
            <p class="font-mono text-xs tracking-widest text-ink-faint uppercase">Hakkımda</p>

            <h1 class="mt-4 max-w-3xl font-display text-4xl leading-[1.3] font-extrabold tracking-tight text-balance sm:text-5xl lg:text-6xl">
                Ankara'da
                <span class="relative inline-block leading-none">
                    memurdum,
                    <x-site.scribble class="absolute -bottom-3 left-0 h-3 w-[92%] text-section" />
                </span>
                Düren'de
                <span class="relative inline-block px-1 leading-none">
                    yazılımcıyım.
                    <x-site.scribble variant="circle" class="absolute -inset-x-4 -top-2.5 h-[calc(100%+1.75rem)] w-[calc(100%+2rem)] text-pen-red" />
                </span>
            </h1>

            <p class="mt-8 max-w-2xl font-hand text-2xl text-ink-soft sm:text-3xl">
                Burası benim karalama defterim: kendime hedefler koyup motivasyon bulduğum, yarınki beni bugünkünden daha iyi yapmaya çalıştığım yer.
                Bu serüvene katılmak isteyen herkesi
                @guest
                    <a href="{{ route('register') }}" class="text-section-ink underline decoration-section decoration-wavy underline-offset-6 [text-decoration-skip-ink:none]">üyeliğe</a>
                @else
                    üyeliğe
                @endguest
                beklerim.
            </p>

            <x-site.share-button class="mt-4 -ml-2" />
        </div>

        <x-site.logo class="stamp mt-4 mr-2 hidden size-32 rotate-12 text-section-ink md:block" />
    </section>

    @unless (app()->isProduction())
        <p class="mt-10 inline-block rotate-[-0.5deg] border border-dashed border-ink/30 px-3 py-1.5 font-hand text-lg text-ink-faint">
            ✎ taslak: hikâyedeki kişisel cümleler yer tutucu, gerçek metni Kadir yazacak
        </p>
    @endunless

    {{-- The road from Ankara to Düren --}}
    <section class="mt-16" aria-labelledby="hikayem">
        <h2 id="hikayem" class="font-display text-3xl font-semibold">Hikâyem</h2>

        <ol class="relative mt-10">
            <li class="road pointer-events-none absolute inset-y-0 left-1 w-6 md:left-1/2 md:-translate-x-1/2" aria-hidden="true"></li>

            @foreach ($stops as $stop)
                @if ($stop['isTurningPoint'])
                    <li class="relative pb-14 pl-12 md:pl-0">
                        <span class="absolute top-6 left-2.5 z-10 size-3.5 rounded-full bg-section ring-4 ring-paper md:hidden" aria-hidden="true"></span>

                        <div class="relative mx-auto max-w-md rotate-[-1.5deg] rounded-sm bg-paper-deep p-5 pt-7 text-center shadow-[0_10px_22px_-12px_rgb(60_40_20/0.5)] dark:shadow-[0_10px_22px_-10px_rgb(0_0_0/0.85)]">
                            <span class="tape -top-3 left-1/2 -translate-x-1/2 -rotate-3"></span>
                            <p class="font-mono text-xs text-ink-faint"><span aria-hidden="true">✈️</span> {{ $stop['years'] }}</p>
                            <p class="mt-1 font-display text-3xl font-extrabold text-section-ink">{{ $stop['place'] }}</p>
                            <h3 class="mt-1 font-hand text-2xl font-bold">{{ $stop['title'] }}</h3>
                            <p class="mt-2 text-ink-soft">{{ $stop['text'] }}</p>
                        </div>
                    </li>
                @else
                    <li class="relative pb-12 pl-12 md:grid md:grid-cols-2 md:gap-16 md:pl-0">
                        <span class="absolute top-1.5 left-2.5 z-10 size-3.5 rounded-full bg-section ring-4 ring-paper md:left-1/2 md:-translate-x-1/2" aria-hidden="true"></span>

                        <div @class(['md:pr-6 md:text-right' => $loop->odd, 'md:col-start-2 md:pl-6' => $loop->even])>
                            <p class="font-mono text-xs text-ink-faint">{{ $stop['years'] }} · {{ $stop['place'] }}</p>
                            <h3 class="mt-1 font-display text-xl leading-tight font-semibold">{{ $stop['title'] }}</h3>
                            <p class="mt-1.5 text-ink-soft">{{ $stop['text'] }}</p>
                        </div>
                    </li>
                @endif
            @endforeach
        </ol>
    </section>

    {{-- Now --}}
    <section class="mt-10" aria-labelledby="su-an">
        <h2 id="su-an" class="font-display text-3xl font-semibold">Şu an</h2>
        <p class="font-hand text-lg text-ink-faint">bu liste diğer sayfalardan kendiliğinden güncellenir</p>

        <ul class="mt-6 flex flex-col gap-3 text-lg">
            @if ($now['project'])
                <li class="flex gap-3">
                    <span aria-hidden="true">🛠️</span>
                    <span><a href="{{ $now['project']['url'] }}" class="font-semibold underline decoration-section decoration-2 underline-offset-4 hover:text-section-ink">{{ $now['project']['name'] }}</a> üzerinde çalışıyorum.</span>
                </li>
            @endif

            @if ($now['series'])
                <li class="flex gap-3">
                    <span aria-hidden="true">📺</span>
                    <span><a href="{{ $now['series']['url'] }}" class="font-semibold underline decoration-section decoration-2 underline-offset-4 hover:text-section-ink">{{ $now['series']['title'] }}</a> izliyorum <span class="font-mono text-sm text-ink-soft">(S{{ $now['series']['season'] }} · B{{ $now['series']['episode'] }})</span></span>
                </li>
            @endif

            @if ($now['chain'])
                <li class="flex gap-3">
                    <span aria-hidden="true">🔥</span>
                    <span>“{{ $now['chain']['title'] }}” zincirinde {{ $now['chain']['period']->atCount($now['chain']['streak']) }}. <a href="{{ route('goals.index') }}" class="text-base text-ink-soft underline decoration-dotted underline-offset-4 hover:text-ink">hedeflerim →</a></span>
                </li>
            @endif

            @if ($now['books'])
                <li class="flex gap-3">
                    <span aria-hidden="true">📚</span>
                    <span>Bu yılki kitap hedefi: <span class="font-mono text-base">{{ $now['books']['current'] }} / {{ $now['books']['target'] }}</span></span>
                </li>
            @endif

            @if ($now['post'])
                <li class="flex gap-3">
                    <span aria-hidden="true">✍️</span>
                    <span>Son yazım: <a href="{{ $now['post']['url'] }}" class="font-semibold underline decoration-section decoration-2 underline-offset-4 hover:text-section-ink">{{ $now['post']['title'] }}</a></span>
                </li>
            @endif
        </ul>
    </section>

    {{-- Toolbox --}}
    <section class="mt-16" aria-labelledby="alet-cantam">
        <h2 id="alet-cantam" class="font-display text-3xl font-semibold">Alet çantam</h2>
        <p class="font-hand text-lg text-ink-faint">defterin kapağına yapıştırılmış stickerlar</p>

        <div class="mt-6 rounded-md bg-paper-deep p-6 sm:p-8">
            <p class="font-hand text-xl text-section-ink">her gün</p>
            <ul class="mt-3 flex flex-wrap gap-3" aria-label="Her gün kullandıklarım">
                @foreach ($toolbox['daily'] as $tool)
                    <li class="sticker" style="--sticker-color: {{ $stickerColors[$loop->index % count($stickerColors)] }}; rotate: {{ $stickerTilts[$loop->index % count($stickerTilts)] }}deg">{{ $tool }}</li>
                @endforeach
            </ul>

            <p class="mt-6 font-hand text-xl text-section-ink">ara sıra</p>
            <ul class="mt-3 flex flex-wrap gap-3" aria-label="Ara sıra kullandıklarım">
                @foreach ($toolbox['sometimes'] as $tool)
                    <li class="sticker text-sm opacity-80 [--sticker-color:var(--color-rule)]" style="rotate: {{ $stickerTilts[($loop->index + 3) % count($stickerTilts)] }}deg">{{ $tool }}</li>
                @endforeach
            </ul>

            <p class="mt-6 font-hand text-xl text-section-ink">konuştuğum diller</p>
            <p class="mt-1">{{ implode(' · ', $toolbox['languages']) }}</p>
        </div>
    </section>

    {{-- Contact --}}
    <section class="mt-16" aria-labelledby="iletisim">
        <h2 id="iletisim" class="font-display text-3xl font-semibold">İletişim</h2>

        <p class="mt-3 max-w-xl font-hand text-2xl">Bir merhaba, bir soru ya da bir film önerisi: hepsine açığım.</p>

        <div class="mt-8 grid items-start gap-10 lg:grid-cols-[minmax(0,1fr)_18rem]">
            <livewire:site.contact-form />

            <div class="relative rotate-[0.8deg] rounded-sm bg-paper-deep p-6 pt-8 shadow-[0_10px_22px_-12px_rgb(60_40_20/0.5)] dark:shadow-[0_10px_22px_-10px_rgb(0_0_0/0.85)]">
                <span class="tape -top-3 -left-3 -rotate-12"></span>

                <p class="font-hand text-xl">başka yerlerde de varım</p>

                <dl class="mt-4 grid grid-cols-1 items-baseline gap-y-1 [&_dd]:[overflow-wrap:anywhere] [&_dt:not(:first-child)]:mt-3">
                    <dt class="font-mono text-[11px] tracking-wider text-ink-faint uppercase">GitHub</dt>
                    <dd><a href="https://github.com/kadirgulec" target="_blank" rel="noopener" class="font-semibold underline decoration-section decoration-2 underline-offset-4 hover:text-section-ink">github.com/kadirgulec</a></dd>

                    <dt class="font-mono text-[11px] tracking-wider text-ink-faint uppercase">CV</dt>
                    <dd><a href="https://kadir.guelec.eu" target="_blank" rel="noopener" class="font-semibold underline decoration-section decoration-2 underline-offset-4 hover:text-section-ink">kadir.guelec.eu</a> <span class="text-sm text-ink-soft">Almanca/İngilizce profesyonel CV için →</span></dd>
                </dl>
            </div>
        </div>
    </section>
</x-layouts::site>
