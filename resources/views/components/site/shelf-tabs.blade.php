@props(['current'])

{{-- The shelf's three pages as strips of washi tape: everything, films and series, books. --}}
@php
    $tabs = [
        'all' => ['Hepsi', route('shelf.index')],
        'watched' => ['İzlediklerim', route('watched.index')],
        'books' => ['Okuduklarım', route('books.index')],
    ];
@endphp

<nav aria-label="Rafım" {{ $attributes->class('flex flex-wrap items-center gap-3') }}>
    @foreach ($tabs as $key => [$label, $url])
        <a
            href="{{ $url }}"
            @if ($key === $current) aria-current="page" @endif
            @class([
                'washi text-sm! transition duration-150 hover:-translate-y-0.5 motion-reduce:transition-none',
                'rotate-[-2deg] font-semibold bg-section! text-section-on!' => $key === $current,
                'odd:rotate-[1.5deg] even:rotate-[-1deg] text-ink-soft! hover:text-ink!' => $key !== $current,
            ])
        >{{ $label }}</a>
    @endforeach
</nav>
