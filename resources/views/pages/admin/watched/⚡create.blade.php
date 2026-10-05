<?php

use App\Actions\Watched\ImportFromTmdb;
use App\Actions\Watched\StorePoster;
use App\Enums\Section;
use App\Enums\WatchableType;
use App\Models\Watchable;
use App\Support\Images\PosterPalette;
use App\Support\Tmdb\Tmdb;
use Illuminate\Http\Client\RequestException;
use Illuminate\Validation\Rule;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;

new #[Layout('layouts::admin'), Title('Film / dizi ekle · İzlediklerim')] class extends Component {
    public string $type = 'film';

    public string $query = '';

    /** @var list<array{id: int, title: string, originalTitle: ?string, year: ?int, posterUrl: ?string, overview: string}> */
    public array $results = [];

    public bool $searched = false;

    public string $manualTitle = '';

    public function search(Tmdb $tmdb): void
    {
        $this->validate([
            'type' => ['required', Rule::enum(WatchableType::class)],
            'query' => ['required', 'string', 'min:2', 'max:120'],
        ], attributes: ['query' => 'arama']);

        try {
            $this->results = $tmdb->search($this->query, WatchableType::from($this->type));
        } catch (RequestException) {
            $this->results = [];
            $this->addError('query', 'TMDB şu an cevap vermiyor. Biraz sonra tekrar dene ya da elle ekle.');
        }

        $this->searched = true;
    }

    public function import(int $tmdbId, ImportFromTmdb $import): void
    {
        $existing = Watchable::query()->where('type', $this->type)->where('tmdb_id', $tmdbId)->first();

        if ($existing !== null) {
            $this->redirectRoute('admin.watched.edit', $existing, navigate: true);

            return;
        }

        $watchable = $import->handle(WatchableType::from($this->type), $tmdbId);

        session()->flash('toast', ['text' => 'TMDB\'den aktarıldı. Şimdi puanını ve izlediğin günü ekle.', 'variant' => 'success']);
        $this->redirectRoute('admin.watched.edit', $watchable, navigate: true);
    }

    /**
     * Not watched yet: the film or series goes to the end of the watchlist.
     */
    public function addToWatchlist(int $tmdbId, ImportFromTmdb $import): void
    {
        $watchable = Watchable::query()->where('type', $this->type)->where('tmdb_id', $tmdbId)->first()
            ?? $import->handle(WatchableType::from($this->type), $tmdbId);

        $watchable->addToWatchlist();

        session()->flash('toast', ['text' => "{$watchable->title} izleyeceğim listesine eklendi.", 'variant' => 'success']);
        $this->redirectRoute('admin.watched.watchlist', navigate: true);
    }

    public function createManually(StorePoster $storePoster): void
    {
        $this->validate([
            'type' => ['required', Rule::enum(WatchableType::class)],
            'manualTitle' => ['required', 'string', 'max:200'],
        ], attributes: ['manualTitle' => 'ad']);

        $watchable = Watchable::query()->create(['type' => $this->type, 'title' => $this->manualTitle]);
        $storePoster->useAccent($watchable, PosterPalette::FALLBACK_ACCENT);

        $this->redirectRoute('admin.watched.edit', $watchable, navigate: true);
    }
}; ?>

<div class="space-y-6">
    <x-admin.page-header heading="Film / dizi ekle" description="TMDB'de ara, seç; bilgiler ve afiş buraya kopyalanır." :dot="Section::Watched->adminDotClass()">
        <x-slot:actions>
            <x-admin.button :href="route('admin.watched.index')" icon="arrow-left" variant="ghost" wire:navigate>İzlediklerim</x-admin.button>
        </x-slot:actions>
    </x-admin.page-header>

    @unless (app(Tmdb::class)->isConfigured())
        <x-admin.card><x-admin.text>TMDB anahtarı yok: <code>.env</code> dosyasına <code>TMDB_API_TOKEN</code> ekle ya da aşağıdan elle ekle.</x-admin.text></x-admin.card>
    @endunless

    <x-admin.card>
        <form wire:submit="search" class="grid gap-3 sm:grid-cols-[10rem_1fr_auto] sm:items-start">
            <x-admin.select wire:model="type" aria-label="Tür" :options="collect(WatchableType::cases())->mapWithKeys(fn ($type) => [$type->value => $type->label()])->all()" />
            <x-admin.input wire:model="query" icon="search" placeholder="Türkçe ya da orijinal adıyla…" aria-label="TMDB'de ara" autofocus />
            <x-admin.button type="submit" variant="primary">Ara</x-admin.button>
        </form>

        @if ($searched)
            <div class="mt-6">
                @if ($results === [])
                    <x-admin.text>Sonuç yok. Başka bir yazım dene ya da aşağıdan elle ekle.</x-admin.text>
                @else
                    <ul class="grid gap-3 sm:grid-cols-2">
                        @foreach ($results as $result)
                            <li wire:key="result-{{ $result['id'] }}" class="flex gap-3 rounded-lg border border-zinc-200 p-3 dark:border-zinc-800">
                                <span class="h-24 w-16 shrink-0 overflow-hidden rounded bg-zinc-100 dark:bg-zinc-800">
                                    @if ($result['posterUrl'])
                                        <img src="{{ $result['posterUrl'] }}" alt="" class="size-full object-cover" loading="lazy" />
                                    @endif
                                </span>
                                <div class="min-w-0 flex-1">
                                    <p class="font-bold">{{ $result['title'] }} <span class="font-normal text-zinc-500">{{ $result['year'] ? '('.$result['year'].')' : '' }}</span></p>
                                    @if ($result['originalTitle'] && $result['originalTitle'] !== $result['title'])
                                        <p class="text-xs text-zinc-500 italic">{{ $result['originalTitle'] }}</p>
                                    @endif
                                    <p class="mt-1 line-clamp-2 text-sm text-zinc-600 dark:text-zinc-400">{{ $result['overview'] }}</p>
                                    <div class="mt-2 flex flex-wrap gap-2">
                                        <x-admin.button size="sm" icon="download" wire:click="import({{ $result['id'] }})">İzledim, aktar</x-admin.button>
                                        <x-admin.button size="sm" variant="ghost" icon="list" wire:click="addToWatchlist({{ $result['id'] }})">İzleyeceğim</x-admin.button>
                                    </div>
                                </div>
                            </li>
                        @endforeach
                    </ul>
                @endif
            </div>
        @endif
    </x-admin.card>

    <x-admin.card>
        <x-slot:heading>Elle ekle</x-slot:heading>
        <form wire:submit="createManually" class="grid gap-3 sm:grid-cols-[1fr_auto] sm:items-start">
            <x-admin.input wire:model="manualTitle" placeholder="Ad" aria-label="Ad" description="TMDB'de olmayan bir şey için. Bilgileri sonra doldurursun." />
            <x-admin.button type="submit">Oluştur</x-admin.button>
        </form>
    </x-admin.card>
</div>
