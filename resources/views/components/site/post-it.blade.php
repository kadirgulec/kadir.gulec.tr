@props([
    'note',
    'size' => 'md',
    'linked' => true,
])

{{--
    A note on a post-it, held onto the page by its tag's washi tape (the tape
    filters the board by that tag). Paper color and tilt come from the note's
    number. "linked" makes the whole card lead to the note's own page.
--}}
<article
    data-color="{{ $note['color'] }}"
    style="--tilt: {{ $note['tilt'] }}deg"
    {{ $attributes->class([
        'post-it relative rotate-(--tilt) rounded-[2px] shadow-[2px_10px_18px_-10px_rgb(60_40_20/0.55)] transition duration-200 ease-out hover:rotate-0 motion-reduce:transition-none dark:shadow-[2px_10px_18px_-8px_rgb(0_0_0/0.9)]',
        'px-5 pt-8 pb-4' => $size === 'md',
        'px-7 pt-11 pb-6 sm:px-9' => $size === 'lg',
    ]) }}
>
    <a
        href="{{ route('notes.index', ['etiket' => $note['tagSlug']]) }}"
        style="--tape-color: {{ \App\Models\Tag::tapeColor($note['tagSlug']) }}"
        class="washi absolute -top-3 left-1/2 z-10 -translate-x-1/2 -rotate-2 font-semibold whitespace-nowrap transition duration-150 hover:-translate-y-0.5 motion-reduce:transition-none"
        aria-label="#{{ $note['tagName'] }} etiketli notlar"
    >#{{ $note['tagName'] }}</a>

    <div @class([
        'prose-notebook',
        'prose-compact' => $size === 'md',
        'text-xl leading-9' => $size === 'lg',
    ])>{{ $note['bodyHtml'] }}</div>

    <footer class="mt-4 flex items-baseline justify-between gap-3 text-(--postit-soft)">
        <time datetime="{{ $note['publishedAt']->toDateString() }}" class="font-mono text-xs">{{ $note['publishedAt']->format('d.m.Y') }}</time>

        @if ($linked)
            <a href="{{ $note['url'] }}" class="font-hand text-xl leading-none font-bold after:absolute after:inset-0 hover:text-(--postit-ink)" aria-label="Not #{{ $note['id'] }}">#{{ $note['id'] }}</a>
        @else
            <span class="font-hand text-xl leading-none font-bold">#{{ $note['id'] }}</span>
        @endif
    </footer>
</article>
