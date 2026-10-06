@props(['name', 'slug', 'active' => false, 'count' => null, 'route' => 'posts.index'])

{{-- A tag as a strip of washi tape. The color is derived from the slug, so a tag looks the same everywhere. "route" is the list it filters. --}}
<a
    href="{{ route($route, ['etiket' => $slug]) }}"
    style="--tape-color: {{ \App\Models\Tag::tapeColor($slug) }}"
    @if ($active) aria-current="page" @endif
    {{ $attributes->class([
        'washi transition duration-150 hover:-translate-y-0.5 motion-reduce:transition-none',
        'rotate-[-2deg] font-semibold bg-section! text-section-on!' => $active,
        'odd:rotate-[1.5deg] even:rotate-[-1deg]' => ! $active,
    ]) }}
>#{{ $name }}@if ($count !== null) <span class="opacity-60">{{ $count }}</span>@endif</a>
