@props(['quote', 'tilt' => 1.5])

{{--
    A collected quote (a book passage or a film line) on a yellow post-it, the
    same paper as the quote blocks inside a review. Where it is ("%34",
    "S2 · B5") goes next to who said it.
--}}
@php($caption = collect([$quote['by'] ? '— '.$quote['by'] : null, $quote['location']])->filter()->implode(' · '))

<figure
    style="--tilt: {{ $tilt }}deg"
    {{ $attributes->class('relative rotate-(--tilt) bg-[#fff1a6] px-6 pt-8 pb-5 shadow-[2px_10px_18px_-10px_rgb(60_40_20/0.55)] transition duration-200 ease-out hover:rotate-0 motion-reduce:transition-none dark:bg-[#4a4322] dark:shadow-[2px_10px_18px_-8px_rgb(0_0_0/0.9)]') }}
>
    <span class="tape -top-3 left-1/2 w-20 -translate-x-1/2 -rotate-3"></span>
    <blockquote class="font-hand text-2xl leading-tight font-bold text-[#3a3020] dark:text-[#f6ecc6]">“{{ $quote['text'] }}”</blockquote>
    @if ($caption !== '')
        <figcaption class="mt-3 font-mono text-xs text-[#6b5c3e] dark:text-[#cfc29a]">{{ $caption }}</figcaption>
    @endif
</figure>
