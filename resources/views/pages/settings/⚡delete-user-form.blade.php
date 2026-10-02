<?php

use Livewire\Component;

new class extends Component {}; ?>

<section class="space-y-4 border-t-2 border-dashed border-rule pt-10">
    <header class="space-y-1">
        <h2 class="font-display text-2xl font-extrabold">Hesabı sil</h2>
        <p class="text-ink-soft">Hesabın ve takiplerin silinir. Yorumların "silinmiş üye" adıyla yerinde kalır, böylece konuşmalar bozulmaz.</p>
    </header>

    <x-site.form.button variant="danger" x-data x-on:click="$dispatch('modal-show', { name: 'confirm-user-deletion' })" data-test="delete-user-button">Hesabımı sil</x-site.form.button>

    <livewire:pages::settings.delete-user-modal />
</section>
