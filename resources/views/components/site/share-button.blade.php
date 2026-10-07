@props(['url' => null])

@php
    // The page's own address: no pagination or tracking noise, but a tag filter is worth sharing.
    $tag = request()->string('etiket')->toString();
    $url ??= $tag !== '' ? url()->current().'?'.http_build_query(['etiket' => $tag]) : url()->current();
@endphp

{{--
    Small "share this page" icon. Opens the device's share sheet (Web Share API) and copies the link
    where there is none; resources/js/site.js wires it up. Without JavaScript it stays hidden.
--}}
<span data-share data-share-url="{{ $url }}" {{ $attributes->merge(['class' => 'hidden items-center gap-1.5 [:where(html.js)_&]:inline-flex']) }}>
    <button
        type="button"
        data-share-button
        aria-label="Bu sayfayı paylaş"
        title="Paylaş"
        class="grid size-9 shrink-0 cursor-pointer place-items-center rounded-full text-ink-soft transition-colors hover:bg-section/15 hover:text-section-ink"
    >
        {{-- Box with an arrow leaving it, drawn a little wobbly like the section icons --}}
        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" class="size-5" aria-hidden="true">
            <path d="M8.6 9.2H6.9c-.8 0-1.4.6-1.4 1.4l.2 8.2c0 .8.6 1.3 1.4 1.3l9.9-.1c.8 0 1.4-.6 1.4-1.4l-.1-8.1c0-.8-.6-1.4-1.4-1.4h-1.6" />
            <path d="M12.1 14.6 11.9 3.6" />
            <path d="M8.7 6.6 12 3.4l3.2 3.3" />
        </svg>
    </button>
    <span data-share-status role="status" class="font-hand text-lg leading-none text-section-ink"></span>
</span>
