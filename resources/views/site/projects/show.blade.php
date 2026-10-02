@use('App\Enums\Section')

<x-layouts::site :section="Section::Projects" :title="$project['name']">
    <a href="{{ route('projects.index') }}" class="font-hand text-xl text-ink-soft hover:text-section-ink">← Projeler</a>

    <header class="mt-8 max-w-3xl">
        <p class="font-mono text-xs tracking-widest text-ink-faint uppercase">başlangıç: {{ $project['since'] }} · {{ implode(' · ', $project['stack']) }}</p>

        <div class="mt-3 flex flex-wrap items-center gap-x-5 gap-y-3">
            <h1 class="font-display text-4xl leading-[1.05] font-extrabold tracking-tight sm:text-5xl">{{ $project['name'] }}</h1>
            <x-site.status-stamp :status="$project['status']" class="scale-110" />
        </div>

        <x-site.scribble class="mt-3 h-3.5 w-40 text-section" />

        <p class="mt-6 text-xl leading-relaxed text-ink-soft">{{ $project['tagline'] }}</p>

        <div class="mt-6 flex flex-wrap items-center gap-x-5 gap-y-3 text-sm font-semibold">
            @if ($project['demoUrl'])
                <a href="{{ $project['demoUrl'] }}" class="rounded-sm bg-section px-3 py-1.5 text-section-on shadow-[2px_2px_0_rgb(0_0_0/0.15)] hover:-translate-y-0.5">demoyu aç ↗</a>
            @endif
            @if ($project['repoUrl'])
                <a href="{{ $project['repoUrl'] }}" class="text-ink-soft underline decoration-dotted underline-offset-4 hover:text-ink">GitHub ↗</a>
            @endif
            @if ($goal)
                <span class="flex items-center gap-2 font-normal">
                    <span class="font-hand text-lg text-ink-faint">bu proje bir hedefe bağlı:</span>
                    <x-site.parent-chip :parent="$goal" />
                </span>
            @endif
        </div>
    </header>

    <div class="mt-12 max-w-3xl rotate-[-0.6deg]">
        <x-site.project-shot :project="$project" />
    </div>

    @if ($project['caseStudy'])
        <div class="mt-16 flex max-w-2xl flex-col gap-12">
            @foreach ($project['caseStudy'] as $section)
                <section>
                    <h2 class="font-display text-2xl font-semibold">{{ $section['heading'] }}</h2>

                    @foreach ($section['paragraphs'] as $paragraph)
                        <p class="mt-4 text-lg leading-8">{{ $paragraph }}</p>
                    @endforeach

                    @if ($section['items'])
                        <ul class="mt-5 grid gap-x-6 gap-y-3 sm:grid-cols-2">
                            @foreach ($section['items'] as $item)
                                <li class="flex gap-3">
                                    <x-site.checkbox :checked="true" class="mt-0.5 size-5 text-ink-faint" />
                                    <span>{{ $item }}</span>
                                </li>
                            @endforeach
                        </ul>
                    @endif
                </section>
            @endforeach
        </div>
    @else
        <p class="mt-12 max-w-2xl font-hand text-2xl text-section-ink">Bu proje için henüz uzun bir yazı yok; ayrıntılar GitHub'daki README'de.</p>
    @endif

    {{-- Additional screenshots as polaroids (the hero image is not repeated) --}}
    @php($extraShots = array_values(array_filter($project['gallery'], fn (array $shot): bool => $shot['url'] !== $project['imageUrl'])))

    @if ($extraShots)
        <section class="mt-16" aria-labelledby="ekran-goruntuleri">
            <h2 id="ekran-goruntuleri" class="font-display text-2xl font-semibold">Daha fazla ekran görüntüsü</h2>

            <div class="mt-8 grid gap-10 sm:grid-cols-2">
                @foreach ($extraShots as $shot)
                    <figure @class(['bg-[#fffdf7] p-2.5 pb-3 shadow-[0_8px_18px_-8px_rgb(60_40_20/0.5)] transition duration-200 hover:rotate-0 motion-reduce:transition-none dark:bg-[#2e2a25]', 'rotate-[-1.5deg]' => $loop->odd, 'rotate-[1.5deg]' => $loop->even])>
                        <img src="{{ $shot['url'] }}" alt="{{ $project['name'] }}: {{ $shot['caption'] }}" class="aspect-[16/10] w-full object-cover object-top" loading="lazy" />
                        <figcaption class="mt-2 text-center font-hand text-xl text-ink-soft">{{ $shot['caption'] }}</figcaption>
                    </figure>
                @endforeach
            </div>
        </section>
    @endif

    @if ($project['devlog'])
        <section class="mt-16 max-w-2xl" aria-labelledby="devlog">
            <h2 id="devlog" class="font-display text-2xl font-semibold">Geliştirme günlüğü</h2>
            <p class="font-hand text-lg text-ink-faint">en yenisi en üstte</p>

            <x-site.logbook :entries="$project['devlog']" class="mt-6" />
        </section>
    @endif
</x-layouts::site>
