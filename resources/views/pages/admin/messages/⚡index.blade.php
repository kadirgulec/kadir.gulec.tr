<?php

use App\Models\ContactMessage;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Attributes\Url;
use Livewire\Component;
use Livewire\WithPagination;

new #[Layout('layouts::admin'), Title('Mesajlar')] class extends Component {
    use WithPagination;

    #[Url(as: 'durum', except: 'hepsi')]
    public string $filter = 'hepsi';

    public function updatedFilter(): void
    {
        $this->resetPage();
    }

    /**
     * @return LengthAwarePaginator<int, ContactMessage>
     */
    #[Computed]
    public function messages(): LengthAwarePaginator
    {
        return ContactMessage::query()
            ->when($this->filter === 'okunmamis', fn ($query) => $query->whereNull('read_at'))
            ->orderByDesc('created_at')
            ->orderByDesc('id')
            ->paginate(20);
    }

    #[Computed]
    public function unreadCount(): int
    {
        return ContactMessage::unreadCount();
    }

    public function markRead(int $id): void
    {
        ContactMessage::query()->whereKey($id)->whereNull('read_at')->update(['read_at' => now()]);
        unset($this->messages, $this->unreadCount);
    }

    public function markUnread(int $id): void
    {
        ContactMessage::query()->whereKey($id)->update(['read_at' => null]);
        unset($this->messages, $this->unreadCount);
    }

    public function delete(int $id): void
    {
        ContactMessage::query()->findOrFail($id)->delete();
        unset($this->messages, $this->unreadCount);
        $this->dispatch('toast', text: 'Mesaj silindi.');
    }
}; ?>

<div>
    <x-admin.page-header heading="Mesajlar" description="Hakkımda sayfasındaki iletişim formundan gelenler. Her mesaj e-postayla da gelir; {{ ContactMessage::KEEP_MONTHS }} aydan eskiler kendiliğinden silinir." />

    <div class="mb-4 flex items-center gap-2">
        <x-admin.button size="sm" :variant="$filter === 'hepsi' ? 'subtle' : 'ghost'" wire:click="$set('filter', 'hepsi')">Hepsi</x-admin.button>
        <x-admin.button size="sm" :variant="$filter === 'okunmamis' ? 'subtle' : 'ghost'" wire:click="$set('filter', 'okunmamis')">Okunmamış <x-admin.badge :color="$this->unreadCount ? 'accent' : 'zinc'">{{ $this->unreadCount }}</x-admin.badge></x-admin.button>
    </div>

    @if ($this->messages->isEmpty())
        <x-admin.card padding="p-0"><x-admin.empty icon="mail" heading="{{ $filter === 'okunmamis' ? 'Okunmamış mesaj yok' : 'Henüz mesaj yok' }}" /></x-admin.card>
    @else
        <div class="space-y-3">
            @foreach ($this->messages as $message)
                <x-admin.card wire:key="message-{{ $message->id }}" @class(['border-l-4 border-l-accent' => ! $message->isRead()])>
                    <div class="flex flex-wrap items-start justify-between gap-3">
                        <div class="min-w-0">
                            <p class="font-bold text-zinc-900 dark:text-white">
                                {{ $message->name }}
                                @unless ($message->isRead())
                                    <x-admin.badge color="accent" class="ml-1">yeni</x-admin.badge>
                                @endunless
                            </p>
                            <p class="font-mono text-xs [overflow-wrap:anywhere] text-zinc-500">{{ $message->email }} · {{ $message->created_at?->format('d.m.Y H:i') }}</p>
                        </div>

                        <div class="flex flex-wrap gap-1">
                            <x-admin.button size="sm" variant="primary" icon="reply" :href="'mailto:'.$message->email.'?subject='.rawurlencode('Re: kadir.gulec.tr')" wire:click="markRead({{ $message->id }})">Cevapla</x-admin.button>
                            @if ($message->isRead())
                                <x-admin.button size="sm" variant="ghost" icon="mail" wire:click="markUnread({{ $message->id }})">Okunmadı yap</x-admin.button>
                            @else
                                <x-admin.button size="sm" variant="ghost" icon="mail-open" wire:click="markRead({{ $message->id }})">Okundu</x-admin.button>
                            @endif
                            <x-admin.button size="sm" variant="ghost" icon="trash-2" wire:click="delete({{ $message->id }})" wire:confirm="{{ $message->name }} adlı kişinin mesajı silinsin mi?">Sil</x-admin.button>
                        </div>
                    </div>

                    <p class="mt-3 max-w-2xl whitespace-pre-line [overflow-wrap:anywhere] text-zinc-700 dark:text-zinc-300">{{ $message->body }}</p>
                </x-admin.card>
            @endforeach
        </div>

        <div class="mt-4">{{ $this->messages->links('components.admin.pagination') }}</div>
    @endif
</div>
