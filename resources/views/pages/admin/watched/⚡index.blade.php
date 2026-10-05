<?php

use App\Enums\Section;
use App\Enums\WatchableType;
use App\Models\Watchable;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Attributes\Url;
use Livewire\Component;
use Livewire\WithPagination;

new #[Layout('layouts::admin'), Title('İzlediklerim')] class extends Component {
    use WithPagination;

    #[Url(as: 'ara', except: '')]
    public string $search = '';

    #[Url(as: 'tur', except: '')]
    public string $type = '';

    public function updated(string $property): void
    {
        if (in_array($property, ['search', 'type'], true)) {
            $this->resetPage();
        }
    }

    /**
     * @return LengthAwarePaginator<int, Watchable>
     */
    #[Computed]
    public function watchables(): LengthAwarePaginator
    {
        return Watchable::query()
            ->with('latestViewing')
            ->when($this->search !== '', fn ($query) => $query->where(fn ($query) => $query->where('title', 'like', '%'.$this->search.'%')->orWhere('original_title', 'like', '%'.$this->search.'%')))
            ->when(WatchableType::tryFrom($this->type), fn ($query, WatchableType $type) => $query->where('type', $type))
            ->orderByDesc('updated_at')
            ->orderByDesc('id')
            ->paginate(25);
    }
}; ?>

<div>
    <x-admin.page-header heading="İzlediklerim" description="Filmler ve diziler, en son dokunduğun en üstte." :dot="Section::Watched->adminDotClass()">
        <x-slot:actions>
            <x-admin.button variant="ghost" icon="list" :href="route('admin.watched.watchlist')" wire:navigate>İzleyeceğim</x-admin.button>
            <x-admin.button variant="primary" icon="plus" :href="route('admin.watched.create')" wire:navigate>Film / dizi ekle</x-admin.button>
        </x-slot:actions>
    </x-admin.page-header>

    <div class="mb-4 grid gap-3 sm:grid-cols-[1fr_12rem]">
        <x-admin.input wire:model.live.debounce.300ms="search" icon="search" placeholder="Adıyla ara…" aria-label="Ara" />
        <x-admin.select wire:model.live="type" aria-label="Tür" placeholder="Filmler ve diziler" :options="collect(WatchableType::cases())->mapWithKeys(fn ($type) => [$type->value => $type->label()])->all()" />
    </div>

    @if ($this->watchables->isEmpty())
        <x-admin.card padding="p-0">
            <x-admin.empty icon="clapperboard" heading="Burada henüz bir şey yok">
                TMDB'den arayıp ekleyebilirsin.
                <x-slot:actions>
                    <x-admin.button variant="primary" icon="plus" size="sm" :href="route('admin.watched.create')" wire:navigate>Film / dizi ekle</x-admin.button>
                </x-slot:actions>
            </x-admin.empty>
        </x-admin.card>
    @else
        <x-admin.table :paginate="$this->watchables">
            <x-admin.table.columns>
                <x-admin.table.column>Ad</x-admin.table.column>
                <x-admin.table.column>Durum</x-admin.table.column>
                <x-admin.table.column>Puan</x-admin.table.column>
                <x-admin.table.column>Son izleme</x-admin.table.column>
                <x-admin.table.column>Yayın</x-admin.table.column>
            </x-admin.table.columns>
            <x-admin.table.rows>
                @foreach ($this->watchables as $watchable)
                    <x-admin.table.row wire:key="watchable-{{ $watchable->id }}">
                        <x-admin.table.cell variant="strong">
                            <a href="{{ route('admin.watched.edit', $watchable) }}" wire:navigate class="flex items-center gap-3 hover:text-accent">
                                <span class="h-12 w-8 shrink-0 overflow-hidden rounded bg-zinc-100 dark:bg-zinc-800" style="{{ $watchable->poster_path ? '' : 'background: linear-gradient(160deg, '.($watchable->poster_colors[0] ?? '#444').', '.($watchable->poster_colors[1] ?? '#999').')' }}">
                                    @if ($watchable->poster_path)
                                        <img src="{{ $watchable->posterUrl(480) }}" alt="" class="size-full object-cover" />
                                    @endif
                                </span>
                                <span>
                                    {{ $watchable->title }}
                                    @if ($watchable->is_favorite)
                                        <x-admin.icon name="star" class="inline size-3.5 fill-current text-section-watched" label="Favori" />
                                    @endif
                                    <span class="block text-xs font-normal text-zinc-500">{{ $watchable->type->label() }} · {{ $watchable->year ?? '—' }}</span>
                                </span>
                            </a>
                        </x-admin.table.cell>
                        <x-admin.table.cell>
                            <div class="flex flex-wrap gap-1">
                                @if ($watchable->isOnWatchlist())
                                    <x-admin.badge color="watched">izleyeceğim</x-admin.badge>
                                @endif
                                @if ($watchable->series_status)
                                    <x-admin.badge>{{ $watchable->series_status->emoji() }} {{ $watchable->series_status->label() }}</x-admin.badge>
                                @endif
                                @if ($watchable->hasPublishedReview())
                                    <x-admin.badge color="watched">yorum</x-admin.badge>
                                @elseif (filled($watchable->review))
                                    <x-admin.badge color="yellow">yorum taslağı</x-admin.badge>
                                @endif
                            </div>
                        </x-admin.table.cell>
                        <x-admin.table.cell class="font-mono">{{ $watchable->rating !== null ? number_format($watchable->rating, 1, ',', '') : '—' }}</x-admin.table.cell>
                        <x-admin.table.cell class="font-mono text-xs">{{ $watchable->latestViewing?->watched_on->format('d.m.Y') ?? '—' }}</x-admin.table.cell>
                        <x-admin.table.cell><x-admin.badge :color="$watchable->publicationState()->color()">{{ $watchable->publicationState()->label() }}</x-admin.badge></x-admin.table.cell>
                    </x-admin.table.row>
                @endforeach
            </x-admin.table.rows>
        </x-admin.table>
    @endif
</div>
