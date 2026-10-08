<?php

use App\Enums\Section;
use App\Models\Watchable;
use Illuminate\Database\Eloquent\Collection;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Locked;
use Livewire\Attributes\Title;
use Livewire\Component;

new #[Layout('layouts::admin'), Title('İzleyeceğim · İzlediklerim')] class extends Component {
    #[Locked]
    public ?int $editingId = null;

    public string $note = '';

    /**
     * @return Collection<int, Watchable>
     */
    #[Computed]
    public function watchables(): Collection
    {
        return Watchable::query()->onWatchlist()->get();
    }

    public function sort(int $id, int $position): void
    {
        Watchable::query()->findOrFail($id)->moveInWatchlist($position);

        unset($this->watchables);
        $this->dispatch('toast', text: 'Sıralama kaydedildi.');
    }

    public function editNote(int $id): void
    {
        $watchable = Watchable::query()->findOrFail($id);

        $this->editingId = $watchable->id;
        $this->note = (string) $watchable->watchlist_note;
        $this->resetValidation();
    }

    public function saveNote(): void
    {
        $this->validate(['note' => ['nullable', 'string', 'max:160']], attributes: ['note' => 'not']);

        Watchable::query()->findOrFail($this->editingId)->addToWatchlist(trim($this->note));

        $this->reset('editingId', 'note');
        unset($this->watchables);
        $this->dispatch('toast', text: 'Not kaydedildi.');
    }

    public function remove(int $id): void
    {
        $watchable = Watchable::query()->findOrFail($id);
        $watchable->removeFromWatchlist();

        unset($this->watchables);
        $this->dispatch('toast', text: "{$watchable->title} listeden çıktı.");
    }
}; ?>

<div>
    <x-admin.page-header heading="İzleyeceğim" description="İzlediklerim sayfasındaki 'Sırada' listesi. Sırayı sürükleyerek değiştir; ilk izlemeyi eklediğinde kayıt listeden kendiliğinden düşer." :dot="Section::Watched->adminDotClass()">
        <x-slot:actions>
            <x-admin.tooltip label="İzleyeceğim listesi nasıl çalışır?">
                <strong>Sitede:</strong> İzlediklerim sayfasındaki "Sırada" bölümü bu listeyi aynı sırayla gösterir. İlk 6 kayıt hep görünür, kalanlar "hepsini göster" ile açılır. Not, afişin altında el yazısıyla çıkar.<br><br>
                <strong>Bağlantı yok:</strong> Kayıtlar henüz izlenmediği (çoğu zaman taslak olduğu) için afişler bir sayfaya bağlanmaz.<br><br>
                <strong>İzleyince:</strong> Kaydın düzenleme ekranında ilk izlemeyi eklediğinde kayıt listeden kendiliğinden düşer. Puanı ve yayını sonra her zamanki gibi verirsin.<br><br>
                <strong>Listeden çıkarmak</strong> kaydı silmez; kayıt İzlediklerim'de taslak olarak kalır.
            </x-admin.tooltip>
            <x-admin.button :href="route('admin.watched.index')" icon="arrow-left" variant="ghost" wire:navigate>İzlediklerim</x-admin.button>
            <x-admin.button variant="primary" icon="plus" :href="route('admin.watched.create')" wire:navigate>Film / dizi ekle</x-admin.button>
        </x-slot:actions>
    </x-admin.page-header>

    @if ($this->watchables->isEmpty())
        <x-admin.card padding="p-0">
            <x-admin.empty icon="clapperboard" heading="Liste boş">
                TMDB'de arayıp sonuçlardaki "İzleyeceğim" düğmesiyle ekleyebilirsin.
                <x-slot:actions>
                    <x-admin.button variant="primary" icon="plus" size="sm" :href="route('admin.watched.create')" wire:navigate>Film / dizi ekle</x-admin.button>
                </x-slot:actions>
            </x-admin.empty>
        </x-admin.card>
    @else
        <ul wire:sort="sort" class="divide-y divide-zinc-100 rounded-xl border border-zinc-200 bg-white shadow-xs dark:divide-zinc-800 dark:border-zinc-800 dark:bg-zinc-900">
            @foreach ($this->watchables as $watchable)
                <li wire:key="watchlist-{{ $watchable->id }}" wire:sort:item="{{ $watchable->id }}" class="flex items-start gap-3 px-3 py-3 sm:gap-4 sm:px-4">
                    <button type="button" wire:sort:handle class="mt-3 cursor-grab text-zinc-400 hover:text-zinc-700 active:cursor-grabbing dark:hover:text-zinc-200" aria-label="{{ $watchable->title }} sırasını değiştir">
                        <x-admin.icon name="grip-vertical" />
                    </button>

                    <span class="h-16 w-11 shrink-0 overflow-hidden rounded bg-zinc-100 dark:bg-zinc-800" style="{{ $watchable->poster_path ? '' : 'background: linear-gradient(160deg, '.implode(', ', $watchable->posterPalette()).')' }}">
                        @if ($watchable->poster_path)
                            <img src="{{ $watchable->posterUrl(480) }}" alt="" class="size-full object-cover" />
                        @endif
                    </span>

                    <div class="min-w-0 flex-1">
                        <a href="{{ route('admin.watched.edit', $watchable) }}" wire:navigate class="font-bold hover:text-accent">{{ $watchable->title }}</a>
                        <p class="text-xs text-zinc-500">{{ $watchable->type->label() }} · {{ $watchable->year ?? '—' }}</p>

                        @if ($editingId === $watchable->id)
                            <form wire:submit="saveNote" class="mt-2 flex items-start gap-2">
                                <x-admin.input wire:model="note" aria-label="Not" placeholder="ör. Ayşe önerdi" class="flex-1" autofocus />
                                <x-admin.button type="submit" size="sm" variant="primary" class="mt-1">Kaydet</x-admin.button>
                                <x-admin.button size="sm" variant="ghost" class="mt-1" wire:click="$set('editingId', null)">Vazgeç</x-admin.button>
                            </form>
                        @elseif ($watchable->watchlist_note)
                            <p class="mt-1 text-sm text-zinc-600 dark:text-zinc-400">{{ $watchable->watchlist_note }}</p>
                        @endif
                    </div>

                    <x-admin.dropdown>
                        <x-slot:trigger>
                            <x-admin.button size="sm" variant="ghost" square icon="ellipsis" aria-label="{{ $watchable->title }}: işlemler" />
                        </x-slot:trigger>
                        <x-admin.dropdown.item icon="pencil" wire:click="editNote({{ $watchable->id }})">{{ $watchable->watchlist_note ? 'Notu düzenle' : 'Not ekle' }}</x-admin.dropdown.item>
                        <x-admin.dropdown.item icon="square-pen" :href="route('admin.watched.edit', $watchable)">Kaydı düzenle</x-admin.dropdown.item>
                        <x-admin.dropdown.separator />
                        <x-admin.dropdown.item icon="minus" wire:click="remove({{ $watchable->id }})">Listeden çıkar</x-admin.dropdown.item>
                    </x-admin.dropdown>
                </li>
            @endforeach
        </ul>
    @endif
</div>
