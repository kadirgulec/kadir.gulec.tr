@props(['route', 'tags', 'activeTag' => null, 'noun'])

{{-- The tag strip above a list ("tümü" plus one tape per tag) and, while a tag is picked, the line that removes it. "noun" names the list's items: "yazılar", "notlar". --}}
<nav class="mt-8 flex flex-wrap items-center gap-x-3 gap-y-3" aria-label="Etiketler">
    <a
        href="{{ route($route) }}"
        @if (! $activeTag) aria-current="page" @endif
        @class([
            'washi rotate-[-1deg] [--tape-color:var(--color-ink-faint)]',
            'bg-section! font-semibold text-section-on!' => ! $activeTag,
        ])
    >tümü</a>

    @foreach ($tags as $tag)
        <x-site.tag :route="$route" :name="$tag['name']" :slug="$tag['slug']" :count="$tag['count']" :active="$activeTag && $activeTag['slug'] === $tag['slug']" />
    @endforeach
</nav>

@if ($activeTag)
    <p class="mt-6 font-hand text-2xl text-section-ink">
        #{{ $activeTag['name'] }} etiketli {{ $noun }} ·
        <a href="{{ route($route) }}" class="text-ink-soft underline decoration-dotted underline-offset-4 hover:text-ink">filtreyi kaldır ×</a>
    </p>
@endif
