@props(['by' => null])

{{-- A favorite line from the film, stuck onto the page like a post-it. --}}
<figure {{ $attributes->merge(['class' => 'relative w-fit max-w-sm rotate-[1.5deg] bg-[#fff1a6] px-6 pt-8 pb-5 shadow-[2px_10px_18px_-10px_rgb(60_40_20/0.55)] transition duration-200 ease-out hover:rotate-0 motion-reduce:transition-none dark:bg-[#4a4322] dark:shadow-[2px_10px_18px_-8px_rgb(0_0_0/0.9)]']) }}>
    <span class="tape -top-3 left-1/2 w-20 -translate-x-1/2 -rotate-3"></span>

    <blockquote class="font-hand text-[1.75rem] leading-tight font-bold text-[#3a3020] dark:text-[#f6ecc6]">
        “{{ $slot }}”
    </blockquote>

    @if ($by)
        <figcaption class="mt-3 font-mono text-xs text-[#6b5c3e] dark:text-[#cfc29a]">— {{ $by }}</figcaption>
    @endif
</figure>
