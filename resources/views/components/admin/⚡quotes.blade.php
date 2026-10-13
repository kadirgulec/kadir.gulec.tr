<?php

use App\Enums\Permission;
use App\Models\Book;
use App\Models\Quote;
use App\Models\Watchable;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Locked;
use Livewire\Component;

/*
 * The quote collection of a book or a film/series on its edit screen: add,
 * edit, hide, reorder and delete. Kept apart from the review, so a book
 * without a review can still have its quotes. It sits inside the edit
 * screen's <form>, so it has no form of its own (forms cannot nest).
 */
new class extends Component {
    /** The quotable kinds (morph names) and who may edit their quotes. */
    private const PERMISSIONS = ['book' => Permission::ManageBooks, 'watchable' => Permission::ManageWatched];

    #[Locked]
    public string $type;

    #[Locked]
    public int $id;

    #[Locked]
    public ?int $editingId = null;

    public string $body = '';

    public string $saidBy = '';

    public string $location = '';

    public function mount(string $type, int $id): void
    {
        abort_unless(array_key_exists($type, self::PERMISSIONS), 404);

        $this->type = $type;
        $this->id = $id;
        $this->authorizeQuotable();
    }

    /**
     * @return Collection<int, Quote>
     */
    #[Computed]
    public function quotes(): Collection
    {
        return $this->quotable()->quotes()->get();
    }

    public function save(): void
    {
        $this->authorizeQuotable();

        $validated = $this->validate([
            'body' => ['required', 'string', 'max:2000'],
            'saidBy' => ['nullable', 'string', 'max:120'],
            'location' => ['nullable', 'string', 'max:60'],
        ], attributes: ['body' => 'alıntı', 'saidBy' => 'kim', 'location' => 'yer']);

        $attributes = [
            'body' => trim($validated['body']),
            'said_by' => trim((string) $validated['saidBy']) ?: null,
            'location' => trim((string) $validated['location']) ?: null,
        ];

        if ($this->editingId !== null) {
            $this->quotable()->quotes()->findOrFail($this->editingId)->update($attributes);
        } else {
            $this->quotable()->quotes()->create([...$attributes, 'published_at' => now()]);
        }

        $this->cancel();
        unset($this->quotes);
        $this->dispatch('toast', text: 'Alıntı kaydedildi.');
    }

    public function edit(int $quoteId): void
    {
        $this->authorizeQuotable();

        $quote = $this->quotable()->quotes()->findOrFail($quoteId);

        $this->editingId = $quote->id;
        $this->body = $quote->body;
        $this->saidBy = (string) $quote->said_by;
        $this->location = (string) $quote->location;
        $this->resetValidation();
    }

    public function cancel(): void
    {
        $this->reset('editingId', 'body', 'saidBy', 'location');
        $this->resetValidation();
    }

    /**
     * A hidden quote stays in the collection but not on the site.
     */
    public function toggle(int $quoteId): void
    {
        $this->authorizeQuotable();

        $quote = $this->quotable()->quotes()->findOrFail($quoteId);
        $quote->update(['published_at' => $quote->published_at === null ? now() : null]);
        unset($this->quotes);
    }

    public function sort(int $quoteId, int $position): void
    {
        $this->authorizeQuotable();

        $this->quotable()->quotes()->findOrFail($quoteId)->moveTo($position);
        unset($this->quotes);
    }

    public function delete(int $quoteId): void
    {
        $this->authorizeQuotable();

        $this->quotable()->quotes()->findOrFail($quoteId)->delete();
        unset($this->quotes);
    }

    private function quotable(): Book|Watchable
    {
        /** @var class-string<Book|Watchable> $class */
        $class = Model::getActualClassNameForMorph($this->type);

        return $class::query()->findOrFail($this->id);
    }

    private function authorizeQuotable(): void
    {
        $this->authorize(self::PERMISSIONS[$this->type]->value);
    }
}; ?>

<x-admin.card>
    <x-slot:heading>
        <span class="inline-flex items-center gap-1.5">
            Alıntılar
            <x-admin.tooltip label="Alıntılar: yardım">
                Burada biriktirdiklerin sayfada post-it koleksiyonu olarak, senin sıranla görünür; yorumdan bağımsızdır, yorumu olmayan {{ $type === 'book' ? 'kitabın' : 'filmin' }} de alıntısı olabilir.<br><br>
                Yorumun akışında geçen bir alıntıyı ise yorumun içine <code>{{ $type === 'book' ? ':::alıntı Kişi' : ':::replik Kişi' }}</code> bloğuyla yaz.<br><br>
                <strong>Gizle</strong> alıntıyı silmeden siteden kaldırır.
            </x-admin.tooltip>
        </span>
    </x-slot:heading>

    <div class="space-y-4">
        <div class="space-y-3">
            <x-admin.textarea wire:model="body" rows="3" :label="$editingId ? 'Alıntıyı düzenle' : 'Yeni alıntı'" placeholder="Altını çizdiğin cümle…" />
            <div class="grid gap-3 sm:grid-cols-[1fr_10rem_auto] sm:items-end" wire:keydown.enter.prevent="save">
                <x-admin.input wire:model="saidBy" label="Kim söyledi" placeholder="opsiyonel" />
                <x-admin.input wire:model="location" label="Yer" :placeholder="$type === 'book' ? '%34, s. 143' : 'S2 · B5'" />
                <div class="flex gap-2 sm:mb-0.5">
                    @if ($editingId)
                        <x-admin.button variant="ghost" wire:click="cancel">Vazgeç</x-admin.button>
                    @endif
                    <x-admin.button icon="quote" wire:click="save">{{ $editingId ? 'Kaydet' : 'Ekle' }}</x-admin.button>
                </div>
            </div>
        </div>

        @if ($this->quotes->isNotEmpty())
            <ul wire:sort="sort" class="divide-y divide-zinc-100 rounded-lg border border-zinc-200 text-sm dark:divide-zinc-800 dark:border-zinc-700">
                @foreach ($this->quotes as $quote)
                    <li wire:key="quote-{{ $quote->id }}" wire:sort:item="{{ $quote->id }}" @class(['flex items-start gap-3 px-3 py-2.5', 'opacity-55' => $quote->published_at === null])>
                        <button type="button" wire:sort:handle class="mt-0.5 cursor-grab text-zinc-400 hover:text-zinc-700 active:cursor-grabbing dark:hover:text-zinc-200" aria-label="Alıntının sırasını değiştir">
                            <x-admin.icon name="grip-vertical" class="size-4" />
                        </button>
                        <div class="min-w-0 flex-1">
                            <p class="whitespace-pre-line">{{ $quote->body }}</p>
                            @if ($quote->said_by || $quote->location)
                                <p class="mt-0.5 text-xs text-zinc-500">— {{ collect([$quote->said_by, $quote->location])->filter()->implode(' · ') }}</p>
                            @endif
                        </div>
                        @if ($quote->published_at === null)
                            <x-admin.badge>gizli</x-admin.badge>
                        @endif
                        <x-admin.dropdown>
                            <x-slot:trigger>
                                <x-admin.button size="sm" variant="ghost" square icon="ellipsis" aria-label="Alıntı: işlemler" />
                            </x-slot:trigger>
                            <x-admin.dropdown.item icon="pencil" wire:click="edit({{ $quote->id }})">Düzenle</x-admin.dropdown.item>
                            <x-admin.dropdown.item :icon="$quote->published_at === null ? 'eye' : 'eye-off'" wire:click="toggle({{ $quote->id }})">{{ $quote->published_at === null ? 'Göster' : 'Gizle' }}</x-admin.dropdown.item>
                            <x-admin.dropdown.separator />
                            <x-admin.dropdown.item icon="trash-2" wire:click="delete({{ $quote->id }})" wire:confirm="Bu alıntı silinsin mi?">Sil</x-admin.dropdown.item>
                        </x-admin.dropdown>
                    </li>
                @endforeach
            </ul>
        @else
            <x-admin.text>Henüz alıntı yok.</x-admin.text>
        @endif
    </div>
</x-admin.card>
