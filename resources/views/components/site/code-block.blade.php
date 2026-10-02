@props(['lang', 'code'])

{{-- A dark card taped onto the page, with the language label and a copy button. --}}
<figure {{ $attributes->merge(['class' => 'relative rounded-md bg-[#24211d] text-[#efe8da] shadow-[0_12px_24px_-14px_rgb(0_0_0/0.7)] ring-1 ring-black/5 dark:bg-[#0f0e0c] dark:ring-white/10']) }} data-code-block>
    <span class="tape -top-3 left-6 w-20 -rotate-6"></span>

    <figcaption class="flex items-center justify-between gap-4 border-b border-white/10 px-4 pt-3 pb-2">
        <span class="font-mono text-[11px] tracking-widest text-[#b8ad9b] uppercase">{{ $lang }}</span>
        <button type="button" data-copy class="cursor-pointer rounded px-2 py-0.5 font-hand text-lg leading-none text-[#e8dcc4] hover:bg-white/10">
            kopyala
        </button>
    </figcaption>

    <pre class="overflow-x-auto px-4 py-4 font-mono text-[13px] leading-relaxed [font-variant-ligatures:none]"><code>{{ $code }}</code></pre>
</figure>
