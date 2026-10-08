<?php

use App\Enums\PublicationState;
use App\Enums\Section;
use App\Models\Post;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Attributes\Url;
use Livewire\Component;
use Livewire\WithPagination;

new #[Layout('layouts::admin'), Title('Yazılar')] class extends Component {
    use WithPagination;

    #[Url(as: 'ara', except: '')]
    public string $search = '';

    #[Url(as: 'durum', except: '')]
    public string $state = '';

    public function updated(string $property): void
    {
        if (in_array($property, ['search', 'state'], true)) {
            $this->resetPage();
        }
    }

    /**
     * @return LengthAwarePaginator<int, Post>
     */
    #[Computed]
    public function posts(): LengthAwarePaginator
    {
        return Post::query()
            ->with('tags')
            ->when($this->search !== '', fn ($query) => $query->where('title', 'like', '%'.$this->search.'%'))
            ->when($this->state === PublicationState::Draft->value, fn ($query) => $query->whereNull('published_at'))
            ->when($this->state === PublicationState::Scheduled->value, fn ($query) => $query->where('published_at', '>', now()))
            ->when($this->state === PublicationState::Published->value, fn ($query) => $query->published())
            // Drafts first, then the newest
            ->orderByRaw('published_at is null desc')
            ->orderByDesc('published_at')
            ->orderByDesc('id')
            ->paginate(25);
    }
}; ?>

<div>
    <x-admin.page-header heading="Yazılar" :dot="Section::Posts->adminDotClass()">
        <x-slot:actions>
            <x-admin.button icon="tag" :href="route('admin.tags.index')" wire:navigate>Etiketler</x-admin.button>
            <x-admin.button variant="primary" icon="plus" :href="route('admin.posts.create')" wire:navigate>Yeni yazı</x-admin.button>
        </x-slot:actions>
    </x-admin.page-header>

    <div class="mb-4 grid gap-3 sm:grid-cols-[1fr_12rem]">
        <x-admin.input wire:model.live.debounce.300ms="search" icon="search" placeholder="Başlıkta ara…" aria-label="Ara" />
        <x-admin.select wire:model.live="state" aria-label="Durum" placeholder="Bütün durumlar" :options="PublicationState::cases()" />
    </div>

    @if ($this->posts->isEmpty())
        <x-admin.card padding="p-0">
            <x-admin.empty icon="file-text" heading="{{ $search || $state ? 'Bu filtreyle yazı yok' : 'Henüz yazı yok' }}">
                @unless ($search || $state)
                    İlk yazını yazınca sitede fihristte görünür.
                    <x-slot:actions>
                        <x-admin.button variant="primary" icon="plus" size="sm" :href="route('admin.posts.create')" wire:navigate>Yeni yazı</x-admin.button>
                    </x-slot:actions>
                @endunless
            </x-admin.empty>
        </x-admin.card>
    @else
        <x-admin.table :paginate="$this->posts">
            <x-admin.table.columns>
                <x-admin.table.column>Başlık</x-admin.table.column>
                <x-admin.table.column>Etiketler</x-admin.table.column>
                <x-admin.table.column>Durum</x-admin.table.column>
                <x-admin.table.column>Tarih</x-admin.table.column>
            </x-admin.table.columns>
            <x-admin.table.rows>
                @foreach ($this->posts as $post)
                    <x-admin.table.row wire:key="post-{{ $post->id }}">
                        <x-admin.table.cell variant="strong">
                            <a href="{{ route('admin.posts.edit', $post) }}" wire:navigate class="flex items-center gap-1.5 hover:text-accent">
                                {{ $post->title }}
                                @if ($post->is_featured)
                                    <x-admin.icon name="star" class="size-3.5 fill-current text-section-posts" label="Öne çıkan" />
                                @endif
                            </a>
                            <span class="font-mono text-xs font-normal text-zinc-500">{{ $post->reading_minutes }} dk</span>
                        </x-admin.table.cell>
                        <x-admin.table.cell>
                            <div class="flex flex-wrap gap-1">
                                @foreach ($post->tags as $tag)
                                    <x-admin.badge color="posts">#{{ $tag->name }}</x-admin.badge>
                                @endforeach
                            </div>
                        </x-admin.table.cell>
                        <x-admin.table.cell><x-admin.badge :color="$post->publicationState()->color()">{{ $post->publicationState()->label() }}</x-admin.badge></x-admin.table.cell>
                        <x-admin.table.cell class="font-mono text-xs">{{ $post->published_at?->format('d.m.Y H:i') ?? '—' }}</x-admin.table.cell>
                    </x-admin.table.row>
                @endforeach
            </x-admin.table.rows>
        </x-admin.table>
    @endif
</div>
