{{--
    Spoiler: the text is crossed out with a thick marker until the reader asks for it.
    Without JavaScript the text is simply visible (the marker only applies under html.js).
--}}
<div data-spoiler {{ $attributes->merge(['class' => 'relative rounded-sm bg-paper-deep px-5 pt-7 pb-5']) }}>
    <span class="stamp absolute -top-3 left-5 -rotate-3 rounded-[3px] border-2 border-pen-red bg-paper px-2 py-0.5 font-mono text-[11px] font-bold tracking-[0.2em] text-pen-red">SPOİLER</span>

    <p class="leading-8"><span class="spoiler-text">{{ $slot }}</span></p>

    <button type="button" data-spoiler-toggle class="mt-3 hidden cursor-pointer font-hand text-xl font-bold text-section-ink underline decoration-wavy decoration-section underline-offset-4 [:where(html.js)_&]:inline-block">
        yine de okumak istiyorum →
    </button>
</div>
