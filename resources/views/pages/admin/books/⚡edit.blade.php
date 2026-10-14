<?php

use App\Actions\Books\StoreCover;
use App\Enums\BookFormat;
use App\Enums\ReadingStatus;
use App\Enums\Section;
use App\Livewire\Forms\BookForm;
use App\Livewire\Forms\WatchableForm;
use App\Models\Book;
use App\Models\Reading;
use App\Support\FormControl;
use App\Support\ReadingProgress;
use Illuminate\Validation\Rule;
use Livewire\Attributes\Layout;
use Livewire\Component;
use Livewire\WithFileUploads;

new #[Layout('layouts::admin')] class extends Component {
    use WithFileUploads;

    /** The languages offered in the language field; any other can be typed. */
    public const LANGUAGES = ['Türkçe', 'İngilizce', 'Almanca', 'Fransızca'];

    public BookForm $form;

    /** @var mixed */
    public $cover = null;

    public string $accent = '';

    public string $startedOn = '';

    /** @var array<int, array{status: string, started_on: string, finished_on: string, progress: string}> */
    public array $readingRows = [];

    public function mount(Book $book): void
    {
        $this->form->setBook($book);
        $this->accent = (string) $book->accent;
        $this->startedOn = now()->toDateString();
        $this->loadReadings();
    }

    public function save(): void
    {
        $this->form->store();
        $this->dispatch('toast', text: 'Kaydedildi.');
    }

    public function publishNow(): void
    {
        $this->form->published_at = FormControl::dateTimeLocal(now());
    }

    public function publishReviewNow(): void
    {
        $this->form->review_published_at = FormControl::dateTimeLocal(now());
    }

    public function updatedCover(StoreCover $storeCover): void
    {
        $this->validate(['cover' => ['image', 'max:10240']], attributes: ['cover' => 'kapak']);

        $storeCover->handle($this->book(), $this->cover->getRealPath());
        $this->cover = null;
        $this->accent = (string) $this->book()->fresh()?->accent;
        $this->dispatch('toast', text: 'Kapak kaydedildi, vurgu rengi kapaktan alındı.');
    }

    public function saveAccent(StoreCover $storeCover): void
    {
        $this->validate(['accent' => ['required', 'hex_color']], attributes: ['accent' => 'vurgu rengi']);

        $storeCover->useAccent($this->book(), strtolower($this->accent));
        $this->dispatch('toast', text: 'Vurgu rengi kaydedildi.');
    }

    /**
     * Starts a new reading (the first one, or a reread).
     */
    public function startReading(): void
    {
        $validated = $this->validate(['startedOn' => ['required', 'date', 'before_or_equal:today']], attributes: ['startedOn' => 'başlangıç']);

        $this->book()->readings()->create(['status' => ReadingStatus::Reading, 'started_on' => $validated['startedOn'], 'progress' => 0]);

        $this->startedOn = now()->toDateString();
        $this->loadReadings();
        $this->dispatch('toast', text: 'Okuma başladı. Şu an okuyorum rafında.');
    }

    /**
     * A finished or abandoned reading without an end date ends today; one in progress has none.
     */
    public function saveReadings(): void
    {
        $this->validate([
            'readingRows.*.status' => ['required', Rule::enum(ReadingStatus::class)],
            'readingRows.*.started_on' => ['required', 'date', 'before_or_equal:today'],
            'readingRows.*.finished_on' => ['nullable', 'date', 'before_or_equal:today'],
            'readingRows.*.progress' => ['nullable', 'string', 'max:20', ReadingProgress::rule()],
        ], attributes: ['readingRows.*.status' => 'durum', 'readingRows.*.started_on' => 'başlangıç', 'readingRows.*.finished_on' => 'bitiş', 'readingRows.*.progress' => 'ilerleme']);

        foreach ($this->readingRows as $id => $row) {
            $status = ReadingStatus::from($row['status']);
            $finishedOn = $status->isInProgress() ? null : ($row['finished_on'] ?: now()->toDateString());

            if ($finishedOn !== null && $finishedOn < $row['started_on']) {
                $this->addError("readingRows.{$id}.finished_on", 'Bitiş, başlangıçtan önce olamaz.');

                return;
            }

            $this->book()->readings()->findOrFail($id)->update([
                'status' => $status,
                'started_on' => $row['started_on'],
                'finished_on' => $finishedOn,
                'progress' => ReadingProgress::parse($row['progress']),
            ]);
        }

        $this->loadReadings();
        $this->dispatch('toast', text: 'Okumalar kaydedildi.');
    }

    public function deleteReading(int $id): void
    {
        $this->book()->readings()->findOrFail($id)->delete();
        $this->loadReadings();
    }

    public function delete(): void
    {
        $this->book()->delete();

        session()->flash('toast', ['text' => 'Silindi.', 'variant' => 'success']);
        $this->redirectRoute('admin.books.index', navigate: true);
    }

    public function addToReadingList(): void
    {
        $this->book()->addToReadingList();
        $this->dispatch('toast', text: 'Okuyacağım listesine eklendi.');
    }

    public function removeFromReadingList(): void
    {
        $this->book()->removeFromReadingList();
        $this->dispatch('toast', text: 'Okuyacağım listesinden çıktı.');
    }

    private function book(): Book
    {
        return $this->form->book ?? abort(404);
    }

    private function loadReadings(): void
    {
        $this->readingRows = $this->book()->readings()->get()->mapWithKeys(fn (Reading $reading): array => [$reading->id => [
            'status' => $reading->status->value,
            'started_on' => $reading->started_on->toDateString(),
            'finished_on' => (string) $reading->finished_on?->toDateString(),
            'progress' => $reading->progress !== null ? (string) $reading->progress : '',
        ]])->all();
    }

    public function render(): mixed
    {
        return $this->view()->title($this->book()->title.' · Okuduklarım');
    }
}; ?>

@php
    $book = $form->book;
    $ratingOptions = collect(WatchableForm::ratingSteps())->mapWithKeys(fn ($step) => [$step => str_replace('.', ',', $step)])->all();
    $statusOptions = collect(ReadingStatus::cases())->mapWithKeys(fn ($status) => [$status->value => $status->emoji().' '.$status->label()])->all();
    $readingIds = array_keys($readingRows);
@endphp

<div class="space-y-6">
    <x-admin.page-header :heading="$book->title" :description="$book->author.($book->year ? ' · '.$book->year : '')" :dot="Section::Shelf->adminDotClass()">
        <x-slot:actions>
            <x-admin.button :href="route('admin.books.index')" icon="arrow-left" variant="ghost" wire:navigate>Okuduklarım</x-admin.button>
            <x-admin.button :href="url($book->publicPath())" icon="external-link" target="_blank">{{ $book->isPublished() ? 'Sitede gör' : 'Önizle' }}</x-admin.button>
        </x-slot:actions>
    </x-admin.page-header>

    <form wire:submit="save" class="grid gap-6 xl:grid-cols-[1fr_20rem]">
        <div class="min-w-0 space-y-6">
            <x-admin.card>
                <x-slot:heading>
                    <span class="inline-flex items-center gap-1.5">
                        Okumalar
                        <x-admin.tooltip label="Okumalar: yardım">
                            Her okuma ayrı bir satır; ikinci satır tekrar okumadır.<br><br>
                            <strong>İlerleme</strong> hep yüzde: Kindle'daki "%43"ü ya da basılı kitapta "143/671" yaz, yüzdeye çevrilir.<br><br>
                            <strong>Bitirdim</strong> ya da <strong>yarım bıraktım</strong> seçip bitişi boş bırakırsan bugün yazılır. Okuyorum ve ara verdim, sitede "şu an okuyorum" rafında; bitenler ve yarım kalanlar günlükte görünür.
                        </x-admin.tooltip>
                    </span>
                </x-slot:heading>

                <div class="space-y-4">
                    @if ($readingRows !== [])
                        <div class="space-y-3">
                            <div class="hidden gap-3 text-xs font-bold text-zinc-500 sm:grid sm:grid-cols-[11rem_9.5rem_9.5rem_6rem_auto]" aria-hidden="true">
                                <span>Durum</span><span>Başladım</span><span>Bitirdim</span><span>İlerleme</span>
                            </div>
                            @foreach ($readingRows as $id => $row)
                                <div wire:key="reading-{{ $id }}" class="grid items-start gap-3 sm:grid-cols-[11rem_9.5rem_9.5rem_6rem_auto]">
                                    <x-admin.select wire:model="readingRows.{{ $id }}.status" :aria-label="'Durum'" :options="$statusOptions" />
                                    <x-admin.input wire:model="readingRows.{{ $id }}.started_on" type="date" aria-label="Başlangıç" title="Başlangıç" />
                                    <x-admin.input wire:model="readingRows.{{ $id }}.finished_on" type="date" aria-label="Bitiş" title="Bitiş" />
                                    <x-admin.input wire:model="readingRows.{{ $id }}.progress" aria-label="İlerleme" placeholder="%" mono />
                                    <div class="flex items-center gap-1">
                                        @if ($id !== $readingIds[0])
                                            <x-admin.badge>önceki</x-admin.badge>
                                        @endif
                                        <x-admin.button size="sm" variant="ghost" square icon="trash-2" class="mt-1" wire:click="deleteReading({{ $id }})" wire:confirm="Bu okuma silinsin mi?" aria-label="Okumayı sil" />
                                    </div>
                                </div>
                            @endforeach
                            <div class="flex justify-end">
                                <x-admin.button wire:click="saveReadings" icon="save">Okumaları kaydet</x-admin.button>
                            </div>
                        </div>
                        <x-admin.separator />
                    @endif

                    <div class="flex flex-wrap items-end gap-3">
                        <x-admin.input wire:model="startedOn" type="date" label="Başladığın gün" class="w-44" />
                        <x-admin.button icon="plus" class="mb-0.5" wire:click="startReading">{{ $readingRows === [] ? 'Okumaya başladım' : 'Yeniden okumaya başladım' }}</x-admin.button>
                    </div>
                </div>
            </x-admin.card>

            <x-admin.card>
                <x-slot:heading>Benim görüşüm</x-slot:heading>

                <div class="grid gap-5 sm:grid-cols-2">
                    <x-admin.select wire:model="form.rating" label="Puan" placeholder="Puan yok" :options="$ratingOptions" description="10 üzerinden, yarım puanlı. Yarım bıraktığın kitaba da verebilirsin." />
                    <x-admin.switch wire:model="form.is_favorite" label="Favori" description="Kapağın köşesine yıldız." class="sm:pt-7" />
                </div>
            </x-admin.card>

            <x-admin.card>
                <x-slot:heading>Yorum</x-slot:heading>
                @if ($book->hasPublishedReview())
                    <x-slot:actions><x-admin.badge color="green">yayında</x-admin.badge></x-slot:actions>
                @endif

                <div class="space-y-4">
                    <x-admin.markdown wire:model="form.review" section="shelf" reviews="book" rows="18" description="Boşsa sayfada özet gösterilir. Yorum, kayıttan ayrı yayınlanır." />
                    <div class="flex flex-wrap items-end gap-2">
                        <x-admin.input wire:model="form.review_published_at" type="datetime-local" label="Yorumun yayın tarihi" description="Boş: yorum taslakta kalır." class="flex-1" />
                        <x-admin.button size="sm" variant="subtle" class="mb-0.5" wire:click="publishReviewNow">Şimdi</x-admin.button>
                    </div>
                </div>
            </x-admin.card>

            <livewire:admin.quotes type="book" :id="$book->id" />

            <x-admin.card>
                <x-slot:heading>Bilgiler</x-slot:heading>

                <div class="grid gap-5 sm:grid-cols-2">
                    <x-admin.input wire:model="form.title" label="Ad" />
                    <x-admin.input wire:model="form.original_title" label="Orijinal ad" description="Çeviriyse." />
                    <x-admin.input wire:model="form.author" label="Yazar" description="Birden çok yazar: virgülle." />
                    <x-admin.input wire:model="form.translator" label="Çevirmen" />
                    <x-admin.input wire:model="form.publisher" label="Yayınevi" />
                    <div class="grid grid-cols-2 gap-3">
                        <x-admin.input wire:model="form.year" type="number" label="Yıl" />
                        <x-admin.input wire:model="form.isbn" label="ISBN" mono />
                    </div>
                    <div>
                        <x-admin.input wire:model="form.language" label="Okuduğun dil" list="book-languages" autocomplete="off" />
                        <datalist id="book-languages">
                            @foreach ($this::LANGUAGES as $language)
                                <option value="{{ $language }}"></option>
                            @endforeach
                        </datalist>
                    </div>
                    <x-admin.select wire:model="form.format" label="Biçim" :options="BookFormat::cases()" />
                    <x-admin.textarea wire:model="form.overview" label="Özet" rows="4" class="sm:col-span-2" description="Yorum yoksa sayfada gösterilir." />
                </div>
            </x-admin.card>
        </div>

        <div class="space-y-6">
            <x-admin.card>
                <x-slot:heading>Yayın</x-slot:heading>
                <x-slot:actions>
                    <x-admin.badge :color="$book->publicationState()->color()">{{ $book->publicationState()->label() }}</x-admin.badge>
                </x-slot:actions>

                <div class="space-y-4">
                    <x-admin.input wire:model="form.published_at" type="datetime-local" label="Yayın tarihi" description="Boş: taslak. Kitap (okumalar + puan) hemen açılabilir, yorum ayrıca." />
                    <div class="flex gap-2">
                        <x-admin.button size="sm" variant="subtle" wire:click="publishNow">Şimdi</x-admin.button>
                        <x-admin.button size="sm" variant="ghost" wire:click="$set('form.published_at', '')">Taslağa al</x-admin.button>
                    </div>
                    <x-admin.separator />
                    <x-admin.input wire:model="form.slug" label="Adres" :description="'/okuduklarim/'.($form->slug ?: '…')" mono />
                    <x-admin.textarea wire:model="form.meta_description" label="SEO açıklaması" rows="2" description="Boşsa özetin başı kullanılır." />
                    <x-admin.button type="submit" variant="primary" class="w-full">Kaydet</x-admin.button>
                </div>
            </x-admin.card>

            <x-admin.card>
                <x-slot:heading>
                    <span class="inline-flex items-center gap-1.5">
                        Okuyacağım
                        <x-admin.tooltip label="Okuyacağım listesi: yardım">
                            Listedeki kitaplar sitede Okuduklarım'daki "Sırada" bölümünde, senin belirlediğin sırayla görünür (ilk 6'sı hep açık); Rafım'da ilk 3'ü. Sıra ve notlar Okuyacağım ekranında düzenlenir. Okumaya başladığında kitap listeden kendiliğinden düşer.
                        </x-admin.tooltip>
                    </span>
                </x-slot:heading>
                @if ($book->isOnReadingList())
                    <x-admin.text>Sırada. Okumaya başladığında listeden kendiliğinden düşer.</x-admin.text>
                    <div class="mt-4 flex flex-wrap gap-2">
                        <x-admin.button size="sm" :href="route('admin.books.reading-list')" wire:navigate>Listeye git</x-admin.button>
                        <x-admin.button size="sm" variant="ghost" wire:click="removeFromReadingList">Listeden çıkar</x-admin.button>
                    </div>
                @else
                    <x-admin.text>Okumak istiyorsan listeye ekle; sitede "Sırada" bölümünde görünür.</x-admin.text>
                    <x-admin.button size="sm" icon="list" class="mt-4" wire:click="addToReadingList">Okuyacağım listesine ekle</x-admin.button>
                @endif
            </x-admin.card>

            <x-admin.card>
                <x-slot:heading>Kapak</x-slot:heading>
                <x-admin.file-upload name="cover" :preview="$book->coverUrl(480)" description="Vurgu rengi kapaktan bir kere hesaplanır. Kapak yoksa bu renklerle çizilir." />

                <div class="mt-4 flex items-end gap-2">
                    <x-admin.input wire:model="accent" type="color" label="Vurgu rengi" class="flex-1 [&_input]:h-10 [&_input]:p-1" />
                    <x-admin.button size="sm" wire:click="saveAccent" class="mb-1">Uygula</x-admin.button>
                </div>
            </x-admin.card>

            <x-admin.card>
                <x-slot:heading>Sil</x-slot:heading>
                <x-admin.text>Okumalar ve alıntılar da silinir.</x-admin.text>
                <x-admin.modal.trigger name="delete-book">
                    <x-admin.button variant="danger" icon="trash-2" class="mt-4 w-full">Sil</x-admin.button>
                </x-admin.modal.trigger>
            </x-admin.card>
        </div>
    </form>

    <x-admin.modal name="delete-book" :heading="$book->title.' silinsin mi?'" description="Bu işlem geri alınamaz.">
        <x-slot:footer>
            <x-admin.modal.close><x-admin.button variant="ghost">Vazgeç</x-admin.button></x-admin.modal.close>
            <x-admin.button variant="danger" wire:click="delete" x-on:click="$el.closest('dialog').close()">Sil</x-admin.button>
        </x-slot:footer>
    </x-admin.modal>
</div>
