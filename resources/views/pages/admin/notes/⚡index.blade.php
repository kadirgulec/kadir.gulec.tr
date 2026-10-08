<?php

use App\Enums\PublicationState;
use App\Enums\Section;
use App\Models\Note;
use App\Models\Tag;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Str;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Attributes\Url;
use Livewire\Component;
use Livewire\WithPagination;

new #[Layout('layouts::admin'), Title('Öğrendiklerim')] class extends Component {
    use WithPagination;

    #[Url(as: 'ara', except: '')]
    public string $search = '';

    #[Url(as: 'etiket', except: '')]
    public string $tag = '';

    #[Url(as: 'durum', except: '')]
    public string $state = '';

    public function updated(string $property): void
    {
        if (in_array($property, ['search', 'tag', 'state'], true)) {
            $this->resetPage();
        }
    }

    /**
     * @return LengthAwarePaginator<int, Note>
     */
    #[Computed]
    public function notes(): LengthAwarePaginator
    {
        return Note::query()
            ->with('tag')
            ->when($this->search !== '', fn ($query) => $query->where('body', 'like', '%'.$this->search.'%'))
            ->when($this->tag !== '', fn ($query) => $query->whereRelation('tag', 'slug', $this->tag))
            ->when($this->state === PublicationState::Draft->value, fn ($query) => $query->whereNull('published_at'))
            ->when($this->state === PublicationState::Scheduled->value, fn ($query) => $query->where('published_at', '>', now()))
            ->when($this->state === PublicationState::Published->value, fn ($query) => $query->published())
            // Drafts first, then the newest
            ->orderByRaw('published_at is null desc')
            ->orderByDesc('published_at')
            ->orderByDesc('id')
            ->paginate(25);
    }

    /**
     * @return array<string, string> slug => "#name"
     */
    #[Computed]
    public function tagOptions(): array
    {
        return Tag::query()->has('notes')->orderBy('name')->get()->mapWithKeys(fn (Tag $tag): array => [$tag->slug => '#'.$tag->name])->all();
    }
}; ?>

<div>
    <x-admin.page-header heading="Öğrendiklerim" description="Küçük notlar, büyük birikim." :dot="Section::Notes->adminDotClass()">
        <x-slot:actions>
            <x-admin.button icon="tag" :href="route('admin.tags.index')" wire:navigate>Etiketler</x-admin.button>
            <x-admin.button variant="primary" icon="plus" :href="route('admin.notes.create')" wire:navigate>Yeni not</x-admin.button>
        </x-slot:actions>
    </x-admin.page-header>

    <div class="mb-4 grid gap-3 sm:grid-cols-[1fr_12rem_12rem]">
        <x-admin.input wire:model.live.debounce.300ms="search" icon="search" placeholder="Notlarda ara…" aria-label="Ara" />
        <x-admin.select wire:model.live="tag" aria-label="Etiket" placeholder="Bütün etiketler" :options="$this->tagOptions" />
        <x-admin.select wire:model.live="state" aria-label="Durum" placeholder="Bütün durumlar" :options="PublicationState::cases()" />
    </div>

    @if ($this->notes->isEmpty())
        <x-admin.card padding="p-0">
            <x-admin.empty icon="sticky-note" heading="{{ $search || $tag || $state ? 'Bu filtreyle not yok' : 'Henüz not yok' }}">
                @unless ($search || $tag || $state)
                    Bugün öğrendiğin küçük bir şeyi yaz, sitede panoya yapışsın.
                    <x-slot:actions>
                        <x-admin.button variant="primary" icon="plus" size="sm" :href="route('admin.notes.create')" wire:navigate>Yeni not</x-admin.button>
                    </x-slot:actions>
                @endunless
            </x-admin.empty>
        </x-admin.card>
    @else
        <x-admin.table :paginate="$this->notes">
            <x-admin.table.columns>
                <x-admin.table.column>Not</x-admin.table.column>
                <x-admin.table.column>Etiket</x-admin.table.column>
                <x-admin.table.column>Durum</x-admin.table.column>
                <x-admin.table.column>Tarih</x-admin.table.column>
            </x-admin.table.columns>
            <x-admin.table.rows>
                @foreach ($this->notes as $note)
                    <x-admin.table.row wire:key="note-{{ $note->id }}">
                        <x-admin.table.cell variant="strong" class="max-w-xl">
                            <a href="{{ route('admin.notes.edit', $note) }}" wire:navigate class="line-clamp-2 font-semibold whitespace-normal hover:text-accent">{{ Str::limit($note->body, 160) }}</a>
                            <span class="font-mono text-xs font-normal text-zinc-500">#{{ $note->id }} · {{ mb_strlen($note->body) }} karakter</span>
                        </x-admin.table.cell>
                        <x-admin.table.cell><x-admin.badge color="notes">#{{ $note->tag->name }}</x-admin.badge></x-admin.table.cell>
                        <x-admin.table.cell><x-admin.badge :color="$note->publicationState()->color()">{{ $note->publicationState()->label() }}</x-admin.badge></x-admin.table.cell>
                        <x-admin.table.cell class="font-mono text-xs">{{ $note->published_at?->format('d.m.Y H:i') ?? '—' }}</x-admin.table.cell>
                    </x-admin.table.row>
                @endforeach
            </x-admin.table.rows>
        </x-admin.table>
    @endif
</div>
