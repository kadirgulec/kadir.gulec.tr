@use('App\Enums\Section')

<x-layouts::site :section="Section::Projects" title="Projeler" description="Yaptıklarım, yapmakta olduklarım ve bir kenara koyduklarım.">
    <p class="font-mono text-xs tracking-widest text-ink-faint uppercase">{{ $projectCount }} proje</p>

    <h1 class="relative mt-3 inline-block font-display text-5xl font-extrabold tracking-tight sm:text-6xl">
        Projeler
        <x-site.scribble class="absolute -bottom-3 left-0 h-3.5 w-full text-section" />
    </h1>

    <p class="mt-8 font-hand text-2xl text-ink-soft">yaptıklarım, yapmakta olduklarım ve bir kenara koyduklarım</p>

    @if (! $featured)
        <p class="mt-14 font-hand text-2xl text-section-ink">Henüz burada bir proje yok, yakında.</p>
    @else
    {{-- Featured --}}
    <x-site.note :section="Section::Projects" tilt="-0.4" label="şu an üzerinde çalıştığım" class="mt-14">
        <div class="grid items-start gap-6 md:grid-cols-[1.2fr_1fr] md:gap-8">
            <a href="{{ $featured['url'] }}" class="block rotate-[-1deg] transition duration-200 hover:rotate-0 motion-reduce:transition-none">
                <x-site.project-shot :project="$featured" />
            </a>

            <div>
                <div class="flex flex-wrap items-center gap-x-4 gap-y-2">
                    <h2 class="font-display text-3xl font-extrabold">
                        <a href="{{ $featured['url'] }}" class="hover:text-section-ink">{{ $featured['name'] }}</a>
                    </h2>
                    <x-site.status-stamp :status="$featured['status']" />
                </div>

                <p class="mt-3 text-ink-soft">{{ $featured['tagline'] }}</p>

                <ul class="mt-4 flex flex-wrap gap-2" aria-label="Kullanılan teknolojiler">
                    @foreach ($featured['stack'] as $technology)
                        <li class="rounded-sm border border-section/60 px-2 py-0.5 font-mono text-xs text-section-ink">{{ $technology }}</li>
                    @endforeach
                </ul>

                @if ($featured['latestLog'])
                    <p class="mt-5 border-l-2 border-section pl-3 text-sm">
                        <span class="font-mono text-xs text-ink-faint">devlog · {{ $featured['latestLog']['date']->locale('tr')->translatedFormat('j F') }}</span><br>
                        {{ $featured['latestLog']['text'] }}
                    </p>
                @endif

                <div class="mt-5 flex flex-wrap gap-x-5 gap-y-2 text-sm font-semibold">
                    <a href="{{ $featured['url'] }}" class="text-section-ink underline decoration-section decoration-2 underline-offset-4">vaka çalışması →</a>
                    @if ($featured['demoUrl'])
                        <a href="{{ $featured['demoUrl'] }}" class="text-ink-soft underline decoration-dotted underline-offset-4 hover:text-ink">demo ↗</a>
                    @endif
                </div>
            </div>
        </div>
    </x-site.note>

    {{-- The rest --}}
    <div class="mt-14 grid gap-x-8 gap-y-12 sm:grid-cols-2">
        @foreach ($projects as $project)
            <article class="flex flex-col">
                <a href="{{ $project['url'] }}" @class(['block transition duration-200 hover:rotate-0 motion-reduce:transition-none', 'rotate-[0.8deg]' => $loop->odd, 'rotate-[-0.8deg]' => $loop->even]) aria-hidden="true" tabindex="-1">
                    <x-site.project-shot :project="$project" />
                </a>

                <div class="mt-5 flex flex-wrap items-center gap-x-3 gap-y-2">
                    <h2 class="font-display text-xl font-semibold">
                        <a href="{{ $project['url'] }}" class="hover:text-section-ink">{{ $project['name'] }}</a>
                    </h2>
                    <x-site.status-stamp :status="$project['status']" class="scale-90" />
                </div>

                <p class="mt-2 text-ink-soft">{{ $project['tagline'] }}</p>

                <ul class="mt-3 flex flex-wrap gap-2" aria-label="Kullanılan teknolojiler">
                    @foreach ($project['stack'] as $technology)
                        <li class="rounded-sm border border-section/60 px-2 py-0.5 font-mono text-xs text-section-ink">{{ $technology }}</li>
                    @endforeach
                </ul>

                <div class="mt-auto flex flex-wrap gap-x-5 gap-y-2 pt-4 text-sm font-semibold">
                    <a href="{{ $project['url'] }}" class="text-section-ink underline decoration-section decoration-2 underline-offset-4">incele →</a>
                    @if ($project['demoUrl'])
                        <a href="{{ $project['demoUrl'] }}" class="text-ink-soft underline decoration-dotted underline-offset-4 hover:text-ink">demo ↗</a>
                    @endif
                    @if ($project['repoUrl'])
                        <a href="{{ $project['repoUrl'] }}" class="text-ink-soft underline decoration-dotted underline-offset-4 hover:text-ink">GitHub ↗</a>
                    @endif
                </div>
            </article>
        @endforeach
    </div>
    @endif
</x-layouts::site>
