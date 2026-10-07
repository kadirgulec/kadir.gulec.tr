@use('App\Enums\Section')

<x-layouts::site :section="Section::Posts" :title="$post['title']" :draft="$post['isDraft']" :description="$post['metaDescription']" :og-image="$post['ogImage']" og-type="article">
    <article>
        <a href="{{ route('posts.index') }}" class="font-hand text-xl text-ink-soft hover:text-section-ink">← Yazılar</a>

        <header class="mt-8 max-w-3xl">
            <p class="font-mono text-xs tracking-widest text-ink-faint uppercase">
                {{ $post['publishedAt']->locale('tr')->translatedFormat('j F Y') }} · {{ $post['readingMinutes'] }} dk okuma
            </p>

            <h1 class="mt-3 font-display text-4xl leading-[1.08] font-extrabold tracking-tight text-balance sm:text-5xl">{{ $post['title'] }}</h1>

            <x-site.scribble variant="double" class="mt-4 h-3.5 w-40 text-section" />

            <div class="mt-6 flex flex-wrap items-center gap-2">
                @foreach ($post['tags'] as $index => $tag)
                    <x-site.tag :name="$tag" :slug="$post['tagSlugs'][$index]" />
                @endforeach
                <x-site.share-button />
            </div>
        </header>

        {{-- Body: a readable column; on wide screens sidenotes sit in the free margin to the right --}}
        <div class="mt-12 max-w-2xl text-lg leading-8 xl:max-w-[34rem]">
            <div class="prose-notebook">{{ $post['bodyHtml'] }}</div>

            {{-- Signed off with the stamp; a reader who got this far is the likeliest to share --}}
            <div class="mt-16 flex flex-wrap items-center gap-4">
                <x-site.logo class="stamp size-16 -rotate-12 text-section-ink" />
                <p class="font-hand text-xl leading-tight text-ink-soft">
                    Kadir<br>
                    {{ $post['publishedAt']->locale('tr')->translatedFormat('j F Y') }}
                </p>
                <x-site.share-button label="beğendiysen paylaş →" class="ml-auto" />
            </div>
        </div>
    </article>

    <livewire:site.comments :post="$postModel" />

    {{-- Older / newer --}}
    @if ($older || $newer)
        <nav class="mt-16 grid gap-4 border-t-2 border-dashed border-rule pt-8 sm:grid-cols-2" aria-label="Diğer yazılar">
            @if ($older)
                <a href="{{ $older['url'] }}" class="group rounded-sm bg-paper-deep p-4">
                    <span class="font-hand text-lg text-ink-faint">← daha eski</span>
                    <span class="mt-1 block font-display text-lg leading-snug font-semibold group-hover:text-section-ink">{{ $older['title'] }}</span>
                </a>
            @endif

            @if ($newer)
                <a href="{{ $newer['url'] }}" class="group rounded-sm bg-paper-deep p-4 text-right sm:col-start-2">
                    <span class="font-hand text-lg text-ink-faint">daha yeni →</span>
                    <span class="mt-1 block font-display text-lg leading-snug font-semibold group-hover:text-section-ink">{{ $newer['title'] }}</span>
                </a>
            @endif
        </nav>
    @endif

    @if ($notes)
        <section class="mt-14" aria-labelledby="kucuk-notlar" data-section="notes">
            <div class="flex flex-wrap items-baseline justify-between gap-x-4 gap-y-1">
                <h2 id="kucuk-notlar" class="font-display text-2xl font-semibold">Bu konuda küçük notlar</h2>
                <a href="{{ route('notes.index') }}" class="text-sm font-semibold text-ink-soft underline decoration-section decoration-2 underline-offset-4 hover:text-ink">öğrendiklerim →</a>
            </div>

            <ul class="mt-10 grid items-start gap-x-8 gap-y-12 sm:grid-cols-2 xl:grid-cols-3">
                @foreach ($notes as $note)
                    <li><x-site.post-it :note="$note" /></li>
                @endforeach
            </ul>
        </section>
    @endif

    @if ($related)
        <section class="mt-14 max-w-2xl" aria-labelledby="ilgili">
            <h2 id="ilgili" class="font-display text-2xl font-semibold">İlgili yazılar</h2>

            <ol class="mt-3">
                @foreach ($related as $other)
                    <li>
                        <a href="{{ $other['url'] }}" class="group flex items-baseline gap-3 py-2">
                            <span class="w-12 shrink-0 font-mono text-xs text-ink-faint">{{ $other['publishedAt']->format('d.m') }}</span>
                            <span class="font-display text-lg leading-snug font-semibold group-hover:text-section-ink">{{ $other['title'] }}</span>
                            <span class="mb-1 hidden min-w-8 flex-1 border-b-2 border-dotted border-rule sm:block" aria-hidden="true"></span>
                            <span class="shrink-0 font-mono text-xs text-ink-faint max-sm:ml-auto">{{ $other['readingMinutes'] }} dk</span>
                        </a>
                    </li>
                @endforeach
            </ol>
        </section>
    @endif
</x-layouts::site>
