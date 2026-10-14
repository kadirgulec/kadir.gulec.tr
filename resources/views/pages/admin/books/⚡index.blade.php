<?php

use App\Enums\ReadingStatus;
use App\Enums\Section;
use App\Models\Book;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Builder;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Attributes\Url;
use Livewire\Component;
use Livewire\WithPagination;

new #[Layout('layouts::admin'), Title('Okuduklarım')] class extends Component {
    use WithPagination;

    #[Url(as: 'ara', except: '')]
    public string $search = '';

    /** A reading status, or "list" for the reading list. */
    #[Url(as: 'durum', except: '')]
    public string $status = '';

    public function updated(string $property): void
    {
        if (in_array($property, ['search', 'status'], true)) {
            $this->resetPage();
        }
    }

    /**
     * @return LengthAwarePaginator<int, Book>
     */
    #[Computed]
    public function books(): LengthAwarePaginator
    {
        return Book::query()
            ->with('latestReading')
            ->when($this->search !== '', fn (Builder $query) => $query->where(fn (Builder $query) => $query->where('title', 'like', '%'.$this->search.'%')->orWhere('original_title', 'like', '%'.$this->search.'%')->orWhere('author', 'like', '%'.$this->search.'%')))
            ->when($this->status === 'list', fn (Builder $query) => $query->whereNotNull('reading_list_position'))
            ->when(ReadingStatus::tryFrom($this->status), fn (Builder $query, ReadingStatus $status) => $query->whereHas('latestReading', fn (Builder $query) => $query->where('status', $status)))
            ->orderByDesc('updated_at')
            ->orderByDesc('id')
            ->paginate(25);
    }

    /**
     * @return array<string, string>
     */
    public function statusOptions(): array
    {
        return ['list' => 'Okuyacağım', ...collect(ReadingStatus::cases())->mapWithKeys(fn (ReadingStatus $status): array => [$status->value => $status->emoji().' '.$status->label()])->all()];
    }
}; ?>

<div>
    <x-admin.page-header heading="Okuduklarım" description="Kitaplar, en son dokunduğun en üstte." :dot="Section::Shelf->adminDotClass()">
        <x-slot:actions>
            <x-admin.button variant="ghost" icon="list" :href="route('admin.books.reading-list')" wire:navigate>Okuyacağım</x-admin.button>
            <x-admin.button variant="primary" icon="plus" :href="route('admin.books.create')" wire:navigate>Kitap ekle</x-admin.button>
        </x-slot:actions>
    </x-admin.page-header>

    <div class="mb-4 grid gap-3 sm:grid-cols-[1fr_12rem]">
        <x-admin.input wire:model.live.debounce.300ms="search" icon="search" placeholder="Adıyla ya da yazarıyla ara…" aria-label="Ara" />
        <x-admin.select wire:model.live="status" aria-label="Durum" placeholder="Bütün kitaplar" :options="$this->statusOptions()" />
    </div>

    @if ($this->books->isEmpty())
        <x-admin.card padding="p-0">
            <x-admin.empty icon="book-open-text" heading="Burada henüz bir şey yok">
                Open Library'de arayıp ekleyebilirsin.
                <x-slot:actions>
                    <x-admin.button variant="primary" icon="plus" size="sm" :href="route('admin.books.create')" wire:navigate>Kitap ekle</x-admin.button>
                </x-slot:actions>
            </x-admin.empty>
        </x-admin.card>
    @else
        <x-admin.table :paginate="$this->books">
            <x-admin.table.columns>
                <x-admin.table.column>Kitap</x-admin.table.column>
                <x-admin.table.column>Durum</x-admin.table.column>
                <x-admin.table.column>Puan</x-admin.table.column>
                <x-admin.table.column>Son okuma</x-admin.table.column>
                <x-admin.table.column>Yayın</x-admin.table.column>
            </x-admin.table.columns>
            <x-admin.table.rows>
                @foreach ($this->books as $book)
                    @php($reading = $book->latestReading)
                    <x-admin.table.row wire:key="book-{{ $book->id }}">
                        <x-admin.table.cell variant="strong">
                            <a href="{{ route('admin.books.edit', $book) }}" wire:navigate class="flex items-center gap-3 hover:text-accent">
                                <span class="h-12 w-8 shrink-0 overflow-hidden rounded bg-zinc-100 dark:bg-zinc-800" style="{{ $book->cover_path ? '' : 'background: linear-gradient(160deg, '.implode(', ', $book->coverPalette()).')' }}">
                                    @if ($book->cover_path)
                                        <img src="{{ $book->coverUrl(480) }}" alt="" class="size-full object-cover" />
                                    @endif
                                </span>
                                <span>
                                    {{ $book->title }}
                                    @if ($book->is_favorite)
                                        <x-admin.icon name="star" class="inline size-3.5 fill-current text-section-watched" label="Favori" />
                                    @endif
                                    <span class="block text-xs font-normal text-zinc-500">{{ $book->author }} · {{ $book->format->label() }}</span>
                                </span>
                            </a>
                        </x-admin.table.cell>
                        <x-admin.table.cell>
                            <div class="flex flex-wrap gap-1">
                                @if ($book->isOnReadingList())
                                    <x-admin.badge color="watched">okuyacağım</x-admin.badge>
                                @endif
                                @if ($reading)
                                    <x-admin.badge>{{ $reading->status->emoji() }} {{ $reading->status->label() }}@if ($reading->status->isInProgress() && $reading->progress !== null) · %{{ $reading->progress }}@endif</x-admin.badge>
                                @endif
                                @if ($book->hasPublishedReview())
                                    <x-admin.badge color="watched">yorum</x-admin.badge>
                                @elseif (filled($book->review))
                                    <x-admin.badge color="yellow">yorum taslağı</x-admin.badge>
                                @endif
                            </div>
                        </x-admin.table.cell>
                        <x-admin.table.cell class="font-mono">{{ $book->rating !== null ? number_format($book->rating, 1, ',', '') : '—' }}</x-admin.table.cell>
                        <x-admin.table.cell class="font-mono text-xs">{{ $reading?->readOn()->format('d.m.Y') ?? '—' }}</x-admin.table.cell>
                        <x-admin.table.cell><x-admin.badge :color="$book->publicationState()->color()">{{ $book->publicationState()->label() }}</x-admin.badge></x-admin.table.cell>
                    </x-admin.table.row>
                @endforeach
            </x-admin.table.rows>
        </x-admin.table>
    @endif
</div>
