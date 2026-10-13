<?php

use App\Actions\Books\ImportFromOpenLibrary;
use App\Actions\Books\StoreCover;
use App\Enums\Section;
use App\Models\Book;
use App\Support\Images\PosterPalette;
use App\Support\OpenLibrary\OpenLibrary;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Http\Client\RequestException;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Locked;
use Livewire\Attributes\Title;
use Livewire\Component;

new #[Layout('layouts::admin'), Title('Kitap ekle · Okuduklarım')] class extends Component {
    public string $query = '';

    /**
     * Set by search() only, so an import can trust what it picks from here.
     *
     * @var list<array{key: string, title: string, author: string, year: ?int, coverId: ?int, isbn: ?string, language: string}>
     */
    #[Locked]
    public array $results = [];

    public bool $searched = false;

    public string $manualTitle = '';

    public string $manualAuthor = '';

    public function search(OpenLibrary $openLibrary): void
    {
        $this->validate(['query' => ['required', 'string', 'min:2', 'max:120']], attributes: ['query' => 'arama']);

        try {
            $this->results = $openLibrary->search($this->query);
        } catch (RequestException|ConnectionException) {
            $this->results = [];
            $this->addError('query', 'Open Library şu an cevap vermiyor. Biraz sonra tekrar dene ya da elle ekle.');
        }

        $this->searched = true;
    }

    public function import(int $index, ImportFromOpenLibrary $import): void
    {
        $book = $import->handle($this->result($index));

        session()->flash('toast', ['text' => 'Open Library\'den aktarıldı. Bilgileri kontrol et, sonra okumayı ekle.', 'variant' => 'success']);
        $this->redirectRoute('admin.books.edit', $book, navigate: true);
    }

    /**
     * Not read yet: the book goes to the end of the reading list.
     */
    public function addToReadingList(int $index, ImportFromOpenLibrary $import): void
    {
        $book = $import->handle($this->result($index));
        $book->addToReadingList();

        session()->flash('toast', ['text' => "{$book->title} okuyacağım listesine eklendi.", 'variant' => 'success']);
        $this->redirectRoute('admin.books.reading-list', navigate: true);
    }

    public function createManually(StoreCover $storeCover): void
    {
        $this->validate([
            'manualTitle' => ['required', 'string', 'max:200'],
            'manualAuthor' => ['required', 'string', 'max:200'],
        ], attributes: ['manualTitle' => 'ad', 'manualAuthor' => 'yazar']);

        $book = Book::query()->create(['title' => trim($this->manualTitle), 'author' => trim($this->manualAuthor)]);
        $storeCover->useAccent($book, PosterPalette::FALLBACK_ACCENT);

        $this->redirectRoute('admin.books.edit', $book, navigate: true);
    }

    /**
     * @return array{key: string, title: string, author: string, year: ?int, coverId: ?int, isbn: ?string, language: string}
     */
    private function result(int $index): array
    {
        return $this->results[$index] ?? abort(404);
    }
}; ?>

<div class="space-y-6">
    <x-admin.page-header heading="Kitap ekle" description="Open Library'de adıyla ya da ISBN'le ara, seç; bilgiler ve kapak buraya kopyalanır." :dot="Section::Shelf->adminDotClass()">
        <x-slot:actions>
            <x-admin.button :href="route('admin.books.index')" icon="arrow-left" variant="ghost" wire:navigate>Okuduklarım</x-admin.button>
        </x-slot:actions>
    </x-admin.page-header>

    <x-admin.card>
        <form wire:submit="search" class="grid gap-3 sm:grid-cols-[1fr_auto] sm:items-start">
            <x-admin.input wire:model="query" icon="search" placeholder="Kitabın adı, yazarı ya da ISBN…" aria-label="Open Library'de ara" description="En isabetlisi ISBN: e-kitabın künyesinde ya da kitabın arka kapağında yazar." autofocus />
            <x-admin.button type="submit" variant="primary">Ara</x-admin.button>
        </form>

        @if ($searched)
            <div class="mt-6">
                @if ($results === [])
                    <x-admin.text>Sonuç yok. Başka bir yazım ya da ISBN dene, ya da aşağıdan elle ekle.</x-admin.text>
                @else
                    <p class="mb-3 flex items-center gap-1.5 text-sm text-zinc-500">
                        Her sonuç için iki seçenek: <strong class="font-semibold text-zinc-700 dark:text-zinc-300">Aktar</strong> ya da <strong class="font-semibold text-zinc-700 dark:text-zinc-300">Okuyacağım</strong>
                        <x-admin.tooltip label="İki seçeneğin farkı">
                            <strong>Aktar:</strong> Ad, yazar, yıl, özet ve kapak taslak bir kitap olarak kopyalanır, düzenleme ekranı açılır. Orada okumayı (okuyorum / bitirdim) eklersin.<br><br>
                            <strong>Okuyacağım:</strong> Kitabı taslak olarak kopyalar ve Okuyacağım listesinin sonuna ekler.<br><br>
                            Open Library'de Türkçe kitapların bilgileri bazen eksik ya da tuhaf yazılı gelir (yayınevi hiç gelmez); hepsi düzenleme ekranında düzeltilebilir. Kitap zaten varsa yenisi açılmaz.
                        </x-admin.tooltip>
                    </p>
                    <ul class="grid gap-3 sm:grid-cols-2">
                        @foreach ($results as $index => $result)
                            <li wire:key="result-{{ $result['key'] }}" class="flex gap-3 rounded-lg border border-zinc-200 p-3 dark:border-zinc-800">
                                <span class="h-24 w-16 shrink-0 overflow-hidden rounded bg-zinc-100 dark:bg-zinc-800">
                                    @if ($result['coverId'])
                                        <img src="{{ OpenLibrary::coverUrl($result['coverId']) }}" alt="" class="size-full object-cover" loading="lazy" />
                                    @endif
                                </span>
                                <div class="min-w-0 flex-1">
                                    <p class="font-bold">{{ $result['title'] }} <span class="font-normal text-zinc-500">{{ $result['year'] ? '('.$result['year'].')' : '' }}</span></p>
                                    <p class="text-sm text-zinc-600 dark:text-zinc-400">{{ $result['author'] ?: 'yazar bilinmiyor' }} · {{ $result['language'] }}</p>
                                    <div class="mt-2 flex flex-wrap gap-2">
                                        <x-admin.button size="sm" icon="download" wire:click="import({{ $index }})">Aktar</x-admin.button>
                                        <x-admin.button size="sm" variant="ghost" icon="list" wire:click="addToReadingList({{ $index }})">Okuyacağım</x-admin.button>
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
        <form wire:submit="createManually" class="grid gap-3 sm:grid-cols-[1fr_1fr_auto] sm:items-start">
            <x-admin.input wire:model="manualTitle" placeholder="Ad" aria-label="Ad" description="Open Library'de olmayan bir kitap için. Bilgileri sonra doldurursun." />
            <x-admin.input wire:model="manualAuthor" placeholder="Yazar" aria-label="Yazar" />
            <x-admin.button type="submit">Oluştur</x-admin.button>
        </form>
    </x-admin.card>
</div>
