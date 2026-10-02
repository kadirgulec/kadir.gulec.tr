<?php

use App\Enums\NotificationFrequency;
use Illuminate\Validation\Rule;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;

new #[Layout('layouts::account'), Title('Bildirimler')] class extends Component {
    public string $frequency = 'daily';

    public bool $newPosts = false;

    public ?string $status = null;

    public function mount(): void
    {
        $this->frequency = auth()->user()->notification_frequency->value;
        $this->newPosts = auth()->user()->notify_new_posts;
    }

    public function save(): void
    {
        $this->validate(['frequency' => ['required', Rule::enum(NotificationFrequency::class)], 'newPosts' => ['boolean']]);

        auth()->user()->forceFill([
            'notification_frequency' => $this->frequency,
            'notify_new_posts' => $this->newPosts,
        ])->save();

        $this->status = 'Kaydedildi.';
    }
}; ?>

<div class="space-y-6">
    <header class="space-y-1">
        <h1 class="font-display text-4xl font-extrabold tracking-tight">Bildirimler</h1>
        <p class="text-ink-soft">Takip ettiklerinde bir şey olunca ne sıklıkla e-posta istersin?</p>
    </header>

    <form wire:submit="save" class="space-y-6">
        <fieldset class="space-y-3">
            <legend class="sr-only">Sıklık</legend>
            @foreach (NotificationFrequency::cases() as $option)
                <label class="flex cursor-pointer items-center gap-3" wire:key="frequency-{{ $option->value }}">
                    <input type="radio" wire:model="frequency" value="{{ $option->value }}" class="size-4 accent-(--color-section-ink)" />
                    <span>{{ $option->label() }}</span>
                </label>
            @endforeach
        </fieldset>

        <x-site.form.checkbox wire:model="newPosts" label="Yeni yazı çıkınca haber ver" />

        <p class="text-sm text-ink-faint">Yorumuna cevap gelince de aynı sıklıkla haber verilir.</p>

        <div class="flex items-center gap-4">
            <x-site.form.button type="submit">Kaydet</x-site.form.button>
            <x-site.form.status :message="$status" />
        </div>
    </form>
</div>
