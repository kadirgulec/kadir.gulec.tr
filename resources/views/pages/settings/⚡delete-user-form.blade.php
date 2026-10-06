<?php

use Livewire\Component;

new class extends Component {}; ?>

{{-- Closed by default: deleting the account takes a deliberate click to even see the button. A plain <details>, no script needed. --}}
<section class="border-t-2 border-dashed border-rule pt-10">
    <details class="group rounded-md border-2 border-dashed border-pen-red/45">
        <summary class="flex cursor-pointer list-none items-center justify-between gap-4 p-4 [&::-webkit-details-marker]:hidden">
            <span>
                <span class="block font-display text-xl font-extrabold text-pen-red">Tehlikeli bölge</span>
                <span class="text-sm text-ink-soft">Hesabını silmek</span>
            </span>
            <span class="font-hand text-lg text-ink-soft">
                <span class="group-open:hidden">aç ↓</span>
                <span class="hidden group-open:inline">kapat ↑</span>
            </span>
        </summary>

        <div class="space-y-4 border-t-2 border-dashed border-pen-red/30 p-4">
            <header class="space-y-1">
                <h2 class="font-display text-2xl font-extrabold">Hesabı sil</h2>
                <p class="text-ink-soft">Hesabın ve takiplerin silinir. Yorumların "silinmiş üye" adıyla yerinde kalır, böylece konuşmalar bozulmaz. Bu geri alınamaz.</p>
            </header>

            <x-site.form.button variant="danger" x-data x-on:click="$dispatch('modal-show', { name: 'confirm-user-deletion' })" data-test="delete-user-button">Hesabımı sil</x-site.form.button>
        </div>
    </details>

    <livewire:pages::settings.delete-user-modal />
</section>
