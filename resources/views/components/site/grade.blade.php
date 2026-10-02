@props(['value'])

{{-- "Teacher's mark": the rating out of 10, circled in red pen. Turkish decimal comma (8,5). --}}
<span {{ $attributes->merge(['class' => 'relative inline-flex size-14 shrink-0 -rotate-6 items-center justify-center font-hand text-[1.7rem] leading-none font-bold text-pen-red']) }}>
    <x-site.scribble variant="circle" class="absolute -inset-1 size-[calc(100%+0.5rem)]" />
    <span class="sr-only">Puan:</span>
    {{ \Illuminate\Support\Number::format($value, maxPrecision: 1, locale: 'tr') }}
    <span class="sr-only">/ 10</span>
</span>
