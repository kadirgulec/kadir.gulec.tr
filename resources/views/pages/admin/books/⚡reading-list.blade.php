<?php

use App\Enums\Section;
use App\Models\Book;
use Illuminate\Database\Eloquent\Collection;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Locked;
use Livewire\Attributes\Title;
use Livewire\Component;

new #[Layout('layouts::admin'), Title('Okuyacağım · Okuduklarım')] class extends Component {
    #[Locked]
    public ?int $editingId = null;

    public string $note = '';

    /**
     * @return Collection<int, Book>
     */
    #[Computed]
    public function books(): Collection
    {
        return Book::query()->onReadingList()->get();
    }

    public function sort(int $id, int $position): void
    {
        Book::query()->findOrFail($id)->moveInReadingList($position);

        unset($this->books);
        $this->dispatch('toast', text: 'Sıralama kaydedildi.');
    }

    public function editNote(int $id): void
    {
        $book = Book::query()->findOrFail($id);

        $this->editingId = $book->id;
        $this->note = (string) $book->reading_list_note;
        $this->resetValidation();
    }

    public function saveNote(): void
    {
        $this->validate(['note' => ['nullable', 'string', 'max:160']], attributes: ['note' => 'not']);

        Book::query()->findOrFail($this->editingId)->addToReadingList(trim($this->note));

        $this->reset('editingId', 'note');
        unset($this->books);
        $this->dispatch('toast', text: 'Not kaydedildi.');
    }

    public function remove(int $id): void
    {
        $book = Book::query()->findOrFail($id);
        $book->removeFromReadingList();

        unset($this->books);
        $this->dispatch('toast', text: "{$book->title} listeden çıktı.");
    }
}; ?>

<div>
    <x-admin.page-header heading="Okuyacağım" description="Okuduklarım sayfasındaki 'Sırada' listesi. Sırayı sürükleyerek değiştir; okumaya başladığında kitap listeden kendiliğinden düşer." :dot="Section::Shelf->adminDotClass()">
        <x-slot:actions>
            <x-admin.tooltip label="Okuyacağım listesi nasıl çalışır?">
                <strong>Sitede:</strong> Okuduklarım sayfasındaki "Sırada" bölümü bu listeyi aynı sırayla gösterir. İlk 6 kitap hep görünür, kalanlar "hepsini göster" ile açılır; Rafım'da ilk 3'ü görünür. Not, kapağın altında el yazısıyla çıkar.<br><br>
                <strong>Bağlantı yok:</strong> Kitaplar henüz okunmadığı (çoğu zaman taslak olduğu) için kapaklar bir sayfaya bağlanmaz.<br><br>
                <strong>Okuyunca:</strong> Kitabın düzenleme ekranında "Okumaya başladım" dediğinde kitap listeden kendiliğinden düşer.<br><br>
                <strong>Listeden çıkarmak</strong> kitabı silmez; kitap Okuduklarım'da taslak olarak kalır.
            </x-admin.tooltip>
            <x-admin.button :href="route('admin.books.index')" icon="arrow-left" variant="ghost" wire:navigate>Okuduklarım</x-admin.button>
            <x-admin.button variant="primary" icon="plus" :href="route('admin.books.create')" wire:navigate>Kitap ekle</x-admin.button>
        </x-slot:actions>
    </x-admin.page-header>

    @if ($this->books->isEmpty())
        <x-admin.card padding="p-0">
            <x-admin.empty icon="book-open-text" heading="Liste boş">
                Open Library'de arayıp sonuçlardaki "Okuyacağım" düğmesiyle ekleyebilirsin.
                <x-slot:actions>
                    <x-admin.button variant="primary" icon="plus" size="sm" :href="route('admin.books.create')" wire:navigate>Kitap ekle</x-admin.button>
                </x-slot:actions>
            </x-admin.empty>
        </x-admin.card>
    @else
        <ul wire:sort="sort" class="divide-y divide-zinc-100 rounded-xl border border-zinc-200 bg-white shadow-xs dark:divide-zinc-800 dark:border-zinc-800 dark:bg-zinc-900">
            @foreach ($this->books as $book)
                <li wire:key="reading-list-{{ $book->id }}" wire:sort:item="{{ $book->id }}" class="flex items-start gap-3 px-3 py-3 sm:gap-4 sm:px-4">
                    <button type="button" wire:sort:handle class="mt-3 cursor-grab text-zinc-400 hover:text-zinc-700 active:cursor-grabbing dark:hover:text-zinc-200" aria-label="{{ $book->title }} sırasını değiştir">
                        <x-admin.icon name="grip-vertical" />
                    </button>

                    <span class="h-16 w-11 shrink-0 overflow-hidden rounded bg-zinc-100 dark:bg-zinc-800" style="{{ $book->cover_path ? '' : 'background: linear-gradient(160deg, '.implode(', ', $book->coverPalette()).')' }}">
                        @if ($book->cover_path)
                            <img src="{{ $book->coverUrl(480) }}" alt="" class="size-full object-cover" />
                        @endif
                    </span>

                    <div class="min-w-0 flex-1">
                        <a href="{{ route('admin.books.edit', $book) }}" wire:navigate class="font-bold hover:text-accent">{{ $book->title }}</a>
                        <p class="text-xs text-zinc-500">{{ $book->author }} · {{ $book->language }}</p>

                        @if ($editingId === $book->id)
                            <form wire:submit="saveNote" class="mt-2 flex items-start gap-2">
                                <x-admin.input wire:model="note" aria-label="Not" placeholder="ör. Ayşe önerdi" class="flex-1" autofocus />
                                <x-admin.button type="submit" size="sm" variant="primary" class="mt-1">Kaydet</x-admin.button>
                                <x-admin.button size="sm" variant="ghost" class="mt-1" wire:click="$set('editingId', null)">Vazgeç</x-admin.button>
                            </form>
                        @elseif ($book->reading_list_note)
                            <p class="mt-1 text-sm text-zinc-600 dark:text-zinc-400">{{ $book->reading_list_note }}</p>
                        @endif
                    </div>

                    <x-admin.dropdown>
                        <x-slot:trigger>
                            <x-admin.button size="sm" variant="ghost" square icon="ellipsis" aria-label="{{ $book->title }}: işlemler" />
                        </x-slot:trigger>
                        <x-admin.dropdown.item icon="pencil" wire:click="editNote({{ $book->id }})">{{ $book->reading_list_note ? 'Notu düzenle' : 'Not ekle' }}</x-admin.dropdown.item>
                        <x-admin.dropdown.item icon="square-pen" :href="route('admin.books.edit', $book)">Kitabı düzenle</x-admin.dropdown.item>
                        <x-admin.dropdown.separator />
                        <x-admin.dropdown.item icon="minus" wire:click="remove({{ $book->id }})">Listeden çıkar</x-admin.dropdown.item>
                    </x-admin.dropdown>
                </li>
            @endforeach
        </ul>
    @endif
</div>
