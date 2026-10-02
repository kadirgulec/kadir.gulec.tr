@props([
    'section',
    'title' => null,
    'accent' => null,
])

<!DOCTYPE html>
<html lang="tr">
    <head>
        <meta charset="utf-8" />
        <meta name="viewport" content="width=device-width, initial-scale=1.0" />

        <title>{{ filled($title) ? $title.' · Kadir Gülec' : 'Kadir Gülec' }}</title>

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

        <div class="mx-auto max-w-6xl px-2 py-3 sm:px-6 sm:py-6 lg:py-10 lg:pr-48">
            <div class="relative">
                <div class="paper relative z-10 min-h-[85dvh] rounded-[3px] shadow-[0_1px_2px_rgb(60_40_20/0.08),0_12px_32px_-12px_rgb(60_40_20/0.25)] dark:shadow-[0_1px_2px_rgb(0_0_0/0.5),0_16px_40px_-12px_rgb(0_0_0/0.7)]">
                    {{-- Notebook margin line --}}
                    <div class="pointer-events-none absolute inset-y-0 left-6 w-px bg-pen-red/30 sm:left-12" aria-hidden="true"></div>
                    {{-- Section color along the page edge, where the tabs attach --}}
                    <div class="pointer-events-none absolute inset-y-0 right-0 w-1.5 rounded-r-[3px] bg-section" aria-hidden="true"></div>

                    <div class="flex min-h-[85dvh] flex-col pr-5 pl-10 sm:pr-10 sm:pl-20">
                        <header class="flex items-center justify-between gap-4 pt-5 sm:pt-7">
                            <a href="{{ route('home') }}" class="group flex items-center gap-3" aria-label="Kadir Gülec, ana sayfa">
                                <x-site.logo class="stamp size-11 -rotate-12 text-home-ink transition-transform duration-200 group-hover:-rotate-3" />
                                <span class="font-hand text-2xl font-bold text-ink">Kadir Gülec</span>
                            </a>

                            <x-site.lamp />
                        </header>

                        <main id="icerik" class="vt-page flex-1 py-8 sm:py-10">
                            {{ $slot }}
                        </main>

                        <footer class="flex flex-wrap items-center justify-between gap-x-6 gap-y-2 border-t border-dashed border-rule py-5 font-mono text-xs text-ink-faint">
                            <p>© {{ now()->year }} Kadir Gülec · bu defter elle tutuluyor</p>

                            <div class="flex gap-4">
                                @if (Route::has('styleguide'))
                                    <a href="{{ route('styleguide') }}" class="underline decoration-dotted underline-offset-4 hover:text-ink">stil rehberi</a>
                                @endif
                                <a href="https://kadir.guelec.eu" class="underline decoration-dotted underline-offset-4 hover:text-ink">CV (DE/EN) ↗</a>
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
