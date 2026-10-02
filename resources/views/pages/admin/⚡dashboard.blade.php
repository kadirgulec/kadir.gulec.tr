<?php

use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;

new #[Layout('layouts::admin'), Title('Pano')] class extends Component {
    //
}; ?>

<div>
    <x-admin.page-header heading="Pano" :description="'Merhaba '.auth()->user()->name.', defterde bugün ne var?'" />

    <div class="grid gap-4 md:grid-cols-2 xl:grid-cols-3">
        <x-admin.card>
            <x-slot:heading>Bugün</x-slot:heading>
            <x-admin.text>Zincirler ve sayısal hedefler buraya gelecek.</x-admin.text>
        </x-admin.card>
    </div>
</div>
