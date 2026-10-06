@props([
    'section',
    'title' => null,
    'accent' => null,
    'draft' => false,
    'noindex' => false,
    'description' => null,
    'ogImage' => null,
    'ogType' => 'website',
])

@php
    $metaDescription = \Illuminate\Support\Str::limit(trim((string) ($description ?? "Kadir Gülec'in dijital defteri: yazılar, izledikleri, hedefleri ve projeleri.")), 200, '…', preserveWords: true);
    $pageTitle = filled($title) ? $title.' · Kadir Gülec' : 'Kadir Gülec';
    $ogImage ??= \App\Support\Og\OgUrl::for('page', $section->value);
@endphp

<!DOCTYPE html>
{{-- data-signed-in: a signed-out page drops this browser's push subscription (resources/js/pwa.js). --}}
<html lang="tr" data-signed-in="{{ auth()->check() ? 'true' : 'false' }}">
    <head>
        <meta charset="utf-8" />
        <meta name="viewport" content="width=device-width, initial-scale=1.0" />

        <title>{{ $pageTitle }}</title>
        <meta name="description" content="{{ $metaDescription }}" />
        <link rel="canonical" href="{{ url()->current() }}" />
        <meta property="og:site_name" content="kadir.gulec.tr" />
        <meta property="og:locale" content="tr_TR" />
        <meta property="og:type" content="{{ $ogType }}" />
        <meta property="og:title" content="{{ $title ?? 'Kadir Gülec' }}" />
        <meta property="og:description" content="{{ $metaDescription }}" />
        <meta property="og:url" content="{{ url()->current() }}" />
        <meta property="og:image" content="{{ $ogImage }}" />
        <meta property="og:image:width" content="1200" />
        <meta property="og:image:height" content="630" />
        <meta name="twitter:card" content="summary_large_image" />
        @if (Route::has('posts.feed'))
            <link rel="alternate" type="application/atom+xml" title="Kadir Gülec · Yazılar" href="{{ route('posts.feed') }}" />
        @endif
        @if (Route::has('notes.feed'))
            <link rel="alternate" type="application/atom+xml" title="Kadir Gülec · Öğrendiklerim" href="{{ route('notes.feed') }}" />
        @endif
        @if ($draft || $noindex)
            <meta name="robots" content="noindex, nofollow" />
        @endif

        {{-- Runs before the first paint so the night notebook never flashes white. --}}
        <script>
            (() => {
                document.documentElement.classList.add('js');
                let stored = null;
                try { stored = localStorage.getItem('theme'); } catch (e) {}
                const isDark = stored ? stored === 'dark' : matchMedia('(prefers-color-scheme: dark)').matches;
                document.documentElement.classList.toggle('dark', isDark);
            })();
        </script>

        <link rel="icon" href="/favicon.svg" type="image/svg+xml">
        <link rel="apple-touch-icon" href="/apple-touch-icon.png">
        <meta name="theme-color" content="#fbf7ee" media="(prefers-color-scheme: light)">
        <meta name="theme-color" content="#1c1a17" media="(prefers-color-scheme: dark)">
        <x-pwa-head />

        @fonts(['fraunces', 'nunito-sans', 'caveat', 'jetbrains-mono'])

        @vite(['resources/css/site.css', 'resources/js/site.js'])
    </head>
    <body
        data-section="{{ $section->value }}"
        @if ($accent) style="--film-accent: {{ $accent }}" @endif
        @class(['min-h-dvh pb-24 antialiased lg:pb-0', 'film-accent' => $accent])
    >
        {{-- Shared SVG filter that gives stamps their uneven, hand-pressed ink. --}}
        <svg class="absolute size-0" aria-hidden="true" focusable="false">
            <filter id="ink-rough">
                <feTurbulence type="fractalNoise" baseFrequency="0.9" numOctaves="2" seed="7" result="noise" />
                <feDisplacementMap in="SourceGraphic" in2="noise" scale="2.5" />
            </filter>
        </svg>

        <a href="#icerik" class="sr-only rounded bg-paper px-3 py-2 focus:not-sr-only focus:fixed focus:top-3 focus:left-3 focus:z-50">
            İçeriğe geç
        </a>

        <x-site.home-stamp />

        <div class="mx-auto max-w-6xl px-2 py-3 sm:px-6 sm:py-6 lg:py-10 lg:pr-48">
            <div class="relative">
                <div class="paper relative z-10 min-h-[85dvh] rounded-[3px] shadow-[0_1px_2px_rgb(60_40_20/0.08),0_12px_32px_-12px_rgb(60_40_20/0.25)] dark:shadow-[0_1px_2px_rgb(0_0_0/0.5),0_16px_40px_-12px_rgb(0_0_0/0.7)]">
                    {{-- Notebook margin line --}}
                    <div class="pointer-events-none absolute inset-y-0 left-6 w-px bg-pen-red/30 sm:left-12" aria-hidden="true"></div>
                    {{-- Section color along the page edge, where the tabs attach --}}
                    <div class="pointer-events-none absolute inset-y-0 right-0 w-1.5 rounded-r-[3px] bg-section" aria-hidden="true"></div>

                    <div class="flex min-h-[85dvh] flex-col pr-5 pl-10 sm:pr-10 sm:pl-20">
                        <header class="flex items-center justify-between gap-4 pt-5 sm:pt-7">
                            <a href="{{ route('home') }}" class="group" aria-label="Kadir Gülec, ana sayfa" data-signature>
                                <x-site.signature class="stamp h-14 w-auto sm:h-16 -rotate-6 text-home-ink transition-transform duration-200 group-hover:-rotate-2" />
                            </a>

                            <x-site.lamp />
                        </header>

                        @if ($draft)
                            <p role="status" class="mt-5 -rotate-1 self-start rounded-sm bg-highlighter px-3 py-1 font-mono text-xs font-semibold tracking-wide text-[#2b2420] uppercase shadow-sm">Taslak · sadece sen görüyorsun</p>
                        @endif

                        <main id="icerik" class="vt-page flex-1 py-8 sm:py-10">
                            {{ $slot }}
                        </main>

                        <footer class="flex flex-wrap items-center justify-between gap-x-6 gap-y-2 border-t border-dashed border-rule py-5 font-mono text-xs text-ink-faint">
                            <p>© {{ now()->year }} Kadir Gülec · bu defter elle tutuluyor</p>

                            <div class="flex flex-wrap gap-4">
                                <a href="{{ route('about') }}" class="underline decoration-dotted underline-offset-4 hover:text-ink">hakkımda</a>
                                <a href="{{ route('privacy') }}" class="underline decoration-dotted underline-offset-4 hover:text-ink">gizlilik</a>
                                <a href="{{ route('imprint') }}" class="underline decoration-dotted underline-offset-4 hover:text-ink">künye</a>
                                @auth
                                    <a href="{{ route('profile.edit') }}" class="underline decoration-dotted underline-offset-4 hover:text-ink">hesabım</a>
                                @else
                                    <a href="{{ route('login') }}" class="underline decoration-dotted underline-offset-4 hover:text-ink">giriş yap</a>
                                @endauth
                                @if (Route::has('styleguide'))
                                    <a href="{{ route('styleguide') }}" class="underline decoration-dotted underline-offset-4 hover:text-ink">stil rehberi</a>
                                @endif
                                <a href="https://kadir.guelec.eu" target="_blank" rel="noopener" class="underline decoration-dotted underline-offset-4 hover:text-ink">CV (DE/EN) ↗</a>
                            </div>
                        </footer>
                    </div>
                </div>

                <x-site.tabs :current="$section" />
            </div>
        </div>

        <x-site.bottom-bar :current="$section" />
    </body>
</html>
