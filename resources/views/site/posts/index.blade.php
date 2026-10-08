@use('App\Enums\Section')

<x-layouts::site :section="Section::Posts" :title="$activeTag ? '#'.$activeTag['name'].' · Yazılar' : 'Yazılar'" description="Kod, kariyer ve arada kalan her şey üzerine Türkçe yazılar.">
    <p class="font-mono text-xs tracking-widest text-ink-faint uppercase">{{ $postCount }} yazı</p>

    <h1 class="relative mt-3 inline-block font-display text-5xl font-extrabold tracking-tight sm:text-6xl">
        Yazılar
        <x-site.scribble class="absolute -bottom-3 left-0 h-3.5 w-full text-section" />
    </h1>

    <div class="mt-8 flex flex-wrap items-center justify-between gap-4">
        <p class="font-hand text-2xl text-ink-soft">kod, kariyer ve arada kalan her şey</p>
        <div class="flex items-center gap-3">
            <livewire:site.subscription kind="posts" />
            <x-site.share-button />
        </div>
    </div>

    <x-site.tag-filter route="posts.index" :tags="$tags" :active-tag="$activeTag" noun="yazılar" />

    {{-- Featured: wide notebook entries --}}
    @if ($featured)
        <section class="mt-14" aria-labelledby="one-cikanlar">
            <h2 id="one-cikanlar" class="font-display text-2xl font-semibold">Öne çıkanlar</h2>

            <div class="mt-8 flex flex-col gap-10">
                @foreach ($featured as $post)
                    <x-site.note :section="Section::Posts" :tilt="$loop->even ? 0.6 : -0.6" :tape="$loop->even ? 'right' : 'left'">
                        <p class="font-mono text-xs text-ink-faint">
                            {{ $post['publishedAt']->locale('tr')->translatedFormat('j F Y') }} · {{ $post['readingMinutes'] }} dk okuma
                        </p>

                        <h3 class="mt-2 font-display text-2xl leading-tight font-semibold text-balance sm:text-3xl">
                            <a href="{{ $post['url'] }}" class="hover:text-section-ink">{{ $post['title'] }}</a>
                        </h3>

                        <p class="mt-3 max-w-2xl text-lg leading-relaxed text-ink-soft">{{ $post['excerpt'] }}</p>

                        <div class="mt-5 flex flex-wrap items-center justify-between gap-4">
                            <div class="flex flex-wrap gap-2">
                                @foreach ($post['tags'] as $index => $tag)
                                    <x-site.tag :name="$tag" :slug="$post['tagSlugs'][$index]" />
                                @endforeach
                            </div>

                            <a href="{{ $post['url'] }}" class="text-sm font-semibold text-section-ink underline decoration-section decoration-2 underline-offset-4">devamını oku →</a>
                        </div>
                    </x-site.note>
                @endforeach
            </div>
        </section>
    @endif

    {{-- Table of contents --}}
    <section class="mt-16" aria-labelledby="fihrist">
        <h2 id="fihrist" class="font-display text-2xl font-semibold">{{ $activeTag ? 'Sonuçlar' : 'Fihrist' }}</h2>

        @if (! $postsByYear && ! $featured)
            <p class="mt-6 font-hand text-2xl text-section-ink">Defterin bu sayfası henüz boş, ilk yazı yolda.</p>
        @endif

        @foreach ($postsByYear as $year => $posts)
            <div class="mt-8">
                <p class="font-mono text-sm font-semibold text-section-ink">{{ $year }}</p>

                <ol class="mt-2">
                    @foreach ($posts as $post)
                        <li>
                            <x-site.post-row :post="$post" class="py-2.5" />
                        </li>
                    @endforeach
                </ol>
            </div>
        @endforeach
    </section>
</x-layouts::site>
