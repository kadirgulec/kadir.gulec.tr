@props(['name', 'slug', 'active' => false, 'count' => null])

{{-- A tag as a strip of washi tape. The color is derived from the slug, so a tag looks the same everywhere. --}}
@php
    $tapeColors = ['var(--color-posts)', 'var(--color-goals)', 'var(--color-about)', 'var(--color-projects)', 'var(--color-watched)', 'var(--color-home)'];
    $tapeColor = $tapeColors[crc32($slug) % count($tapeColors)];
@endphp

<a
    href="{{ route('posts.index', ['etiket' => $slug]) }}"
    style="--tape-color: {{ $tapeColor }}"
    @if ($active) aria-current="page" @endif
    {{ $attributes->class([
        'washi transition duration-150 hover:-translate-y-0.5 motion-reduce:transition-none',
        'rotate-[-2deg] font-semibold bg-section! text-section-on!' => $active,
        'odd:rotate-[1.5deg] even:rotate-[-1deg]' => ! $active,
    ]) }}
>#{{ $name }}@if ($count !== null) <span class="opacity-60">{{ $count }}</span>@endif</a>
