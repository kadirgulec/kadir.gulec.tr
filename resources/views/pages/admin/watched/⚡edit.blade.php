<?php

use App\Actions\Watched\ImportFromTmdb;
use App\Actions\Watched\StorePoster;
use App\Enums\SeriesStatus;
use App\Enums\Section;
use App\Livewire\Forms\WatchableForm;
use App\Models\Season;
use App\Models\Watchable;
use Illuminate\Http\Client\RequestException;
use Illuminate\Validation\Rule;
use Livewire\Attributes\Layout;
use Livewire\Component;
use Livewire\WithFileUploads;

new #[Layout('layouts::admin')] class extends Component {
    use WithFileUploads;

    public WatchableForm $form;

    /** @var mixed */
    public $poster = null;

    public string $accent = '';

    public string $viewingDate = '';

    public string $viewingPlace = '';

    public string $viewingNote = '';

    /** @var array<int, array{episode_count: int|string, rating: string, note: string}> */
    public array $seasonRows = [];

    public function mount(Watchable $watchable): void
    {
        $this->form->setWatchable($watchable);
        $this->accent = (string) $watchable->accent;
        $this->viewingDate = now()->toDateString();
        $this->loadSeasons();
    }

    public function save(): void
    {
        $this->form->store();
        $this->dispatch('toast', text: 'Kaydedildi.');
    }

    public function publishNow(): void
    {
        $this->form->published_at = now()->format('Y-m-d\TH:i');
    }

    public function publishReviewNow(): void
    {
        $this->form->review_published_at = now()->format('Y-m-d\TH:i');
    }

    public function refreshFromTmdb(ImportFromTmdb $import): void
    {
        $watchable = $this->watchable();
        abort_if($watchable->tmdb_id === null, 404);

        try {
            $import->handle($watchable->type, $watchable->tmdb_id, $watchable);
        } catch (RequestException) {
            $this->dispatch('toast', text: 'TMDB şu an cevap vermiyor.', variant: 'danger');

            return;
        }

        $this->form->setWatchable($watchable->fresh() ?? $watchable);
        $this->loadSeasons();
        $this->dispatch('toast', text: 'Bilgiler TMDB\'den yenilendi. Puanın, yorumun ve notların yerinde.');
    }

    public function updatedPoster(StorePoster $storePoster): void
    {
        $this->validate(['poster' => ['image', 'max:10240']], attributes: ['poster' => 'afiş']);

        $storePoster->handle($this->watchable(), $this->poster->getRealPath());
        $this->poster = null;
        $this->accent = (string) $this->watchable()->fresh()?->accent;
        $this->dispatch('toast', text: 'Afiş kaydedildi, vurgu rengi afişten alındı.');
    }

    public function saveAccent(StorePoster $storePoster): void
    {
        $this->validate(['accent' => ['required', 'hex_color']], attributes: ['accent' => 'vurgu rengi']);

        $storePoster->useAccent($this->watchable(), strtolower($this->accent));
        $this->dispatch('toast', text: 'Vurgu rengi kaydedildi.');
    }

    public function addViewing(): void
    {
        $validated = $this->validate([
            'viewingDate' => ['required', 'date', 'before_or_equal:today'],
            'viewingPlace' => ['nullable', 'string', 'max:120'],
            'viewingNote' => ['nullable', 'string', 'max:160'],
        ], attributes: ['viewingDate' => 'tarih', 'viewingPlace' => 'yer', 'viewingNote' => 'not']);

        $this->watchable()->viewings()->create([
            'watched_on' => $validated['viewingDate'],
            'place' => $validated['viewingPlace'] ?: null,
            'note' => $validated['viewingNote'] ?: null,
        ]);

        $this->reset('viewingPlace', 'viewingNote');
        $this->viewingDate = now()->toDateString();
        $this->dispatch('toast', text: 'Günlüğe eklendi.');
    }

    public function deleteViewing(int $id): void
    {
        $this->watchable()->viewings()->findOrFail($id)->delete();
    }

    public function addSeason(): void
    {
        $watchable = $this->watchable();
        abort_unless($watchable->isSeries(), 404);

        $watchable->seasons()->create(['number' => (int) $watchable->seasons()->max('number') + 1]);
        $this->loadSeasons();
    }

    public function saveSeasons(): void
    {
        $this->validate([
            'seasonRows.*.episode_count' => ['required', 'integer', 'between:0,500'],
            'seasonRows.*.rating' => ['nullable', Rule::in(WatchableForm::ratingSteps())],
            'seasonRows.*.note' => ['nullable', 'string', 'max:200'],
        ], attributes: ['seasonRows.*.episode_count' => 'bölüm sayısı', 'seasonRows.*.rating' => 'sezon puanı', 'seasonRows.*.note' => 'sezon notu']);

        foreach ($this->seasonRows as $id => $row) {
            $this->watchable()->seasons()->findOrFail($id)->update([
                'episode_count' => (int) $row['episode_count'],
                'rating' => $row['rating'] !== '' ? (float) $row['rating'] : null,
                'note' => $row['note'] !== '' ? $row['note'] : null,
            ]);
        }

        $this->dispatch('toast', text: 'Sezonlar kaydedildi.');
    }

    public function deleteSeason(int $id): void
    {
        $this->watchable()->seasons()->findOrFail($id)->delete();
        $this->loadSeasons();
    }

    public function delete(): void
    {
        $this->watchable()->delete();

        session()->flash('toast', ['text' => 'Silindi.', 'variant' => 'success']);
        $this->redirectRoute('admin.watched.index', navigate: true);
    }

    private function watchable(): Watchable
    {
        return $this->form->watchable ?? abort(404);
    }

    private function loadSeasons(): void
    {
        $this->seasonRows = $this->watchable()->seasons()->get()->mapWithKeys(fn (Season $season): array => [$season->id => [
            'episode_count' => $season->episode_count,
            'rating' => $season->rating !== null ? number_format($season->rating, 1, '.', '') : '',
            'note' => (string) $season->note,
        ]])->all();
    }

    public function render(): mixed
    {
        return $this->view()->title($this->watchable()->title.' · İzlediklerim');
    }
}; ?>

@php
    $watchable = $form->watchable;
    $ratingOptions = collect(WatchableForm::ratingSteps())->mapWithKeys(fn ($step) => [$step => str_replace('.', ',', $step)])->all();
    $seasons = $watchable->seasons()->get()->keyBy('id');
    $viewings = $watchable->viewings()->get();
@endphp

<div class="space-y-6">
    <x-admin.page-header :heading="$watchable->title" :description="$watchable->type->label().($watchable->year ? ' · '.$watchable->year : '')" :dot="Section::Watched->adminDotClass()">
        <x-slot:actions>
            <x-admin.button :href="route('admin.watched.index')" icon="arrow-left" variant="ghost" wire:navigate>İzlediklerim</x-admin.button>
            @if ($watchable->tmdb_id)
                <x-admin.button icon="refresh-cw" wire:click="refreshFromTmdb">TMDB'den yenile</x-admin.button>
            @endif
            <x-admin.button :href="route('watched.show', ['type' => $watchable->type->routeSegment(), 'slug' => $watchable->slug])" icon="external-link" target="_blank">{{ $watchable->isPublished() ? 'Sitede gör' : 'Önizle' }}</x-admin.button>
        </x-slot:actions>
    </x-admin.page-header>

    <form wire:submit="save" class="grid gap-6 xl:grid-cols-[1fr_20rem]">
        <div class="min-w-0 space-y-6">
            <x-admin.card>
                <x-slot:heading>Benim görüşüm</x-slot:heading>

                <div class="grid gap-5 sm:grid-cols-2">
                    <x-admin.select wire:model="form.rating" label="Puan" placeholder="Puan yok" :options="$ratingOptions" description="10 üzerinden, yarım puanlı. Tek puan: güncel görüşün." />
                    <x-admin.switch wire:model="form.is_favorite" label="Favori" description="Afişin köşesine yıldız." class="sm:pt-7" />

                    @if ($watchable->isSeries())
                        <x-admin.select wire:model="form.series_status" label="Dizi durumu" placeholder="—" :options="collect(SeriesStatus::cases())->mapWithKeys(fn ($status) => [$status->value => $status->emoji().' '.$status->label()])->all()" class="sm:col-span-2" />
                        <x-admin.input wire:model="form.current_season" type="number" min="1" label="Şu anki sezon" />
                        <x-admin.input wire:model="form.current_episode" type="number" min="0" label="Şu anki bölüm" />
                    @endif
                </div>
            </x-admin.card>

            <x-admin.card>
                <x-slot:heading>Yorum</x-slot:heading>
                @if ($watchable->hasPublishedReview())
                    <x-slot:actions><x-admin.badge color="green">yayında</x-admin.badge></x-slot:actions>
                @endif

                <div class="space-y-4">
                    <x-admin.markdown wire:model="form.review" section="watched" reviews rows="18" description="Boşsa sayfada özet gösterilir. Yorum, kayıttan ayrı yayınlanır." />
                    <div class="flex flex-wrap items-end gap-2">
                        <x-admin.input wire:model="form.review_published_at" type="datetime-local" label="Yorumun yayın tarihi" description="Boş: yorum taslakta kalır." class="flex-1" />
                        <x-admin.button size="sm" variant="subtle" class="mb-0.5" wire:click="publishReviewNow">Şimdi</x-admin.button>
                    </div>
                </div>
            </x-admin.card>

            <x-admin.card>
                <x-slot:heading>İzleme günlüğü</x-slot:heading>

                <div class="space-y-4">
                    <div class="grid gap-3 sm:grid-cols-[10rem_1fr_1fr]">
                        <x-admin.input wire:model="viewingDate" type="date" label="Tarih" />
                        <x-admin.input wire:model="viewingPlace" label="Nerede" placeholder="Sinemada, Netflix, evde…" />
                        <x-admin.input wire:model="viewingNote" label="Not" :placeholder="$watchable->isSeries() ? 'S2\'yi bitirdim' : 'opsiyonel'" />
                    </div>
                    <div class="flex justify-end">
                        <x-admin.button icon="plus" wire:click="addViewing">Günlüğe ekle</x-admin.button>
                    </div>

                    @if ($viewings->isNotEmpty())
                        <ul class="divide-y divide-zinc-100 rounded-lg border border-zinc-200 text-sm dark:divide-zinc-800 dark:border-zinc-700">
                            @foreach ($viewings as $viewing)
                                <li wire:key="viewing-{{ $viewing->id }}" class="flex items-center gap-3 px-3 py-2">
                                    <span class="w-24 font-mono text-xs text-zinc-500">{{ $viewing->watched_on->format('d.m.Y') }}</span>
                                    <span class="flex-1">{{ $viewing->place ?? '—' }} @if ($viewing->note) <span class="text-zinc-500">· {{ $viewing->note }}</span> @endif</span>
                                    @if (! $loop->last)
                                        <x-admin.badge>tekrar</x-admin.badge>
                                    @endif
                                    <x-admin.button size="sm" variant="ghost" square icon="trash-2" wire:click="deleteViewing({{ $viewing->id }})" wire:confirm="Bu izleme günlükten silinsin mi?" aria-label="Sil" />
                                </li>
                            @endforeach
                        </ul>
                    @else
                        <x-admin.text>Henüz izleme yok. Ekleyince sitedeki günlükte görünür.</x-admin.text>
                    @endif
                </div>
            </x-admin.card>

            @if ($watchable->isSeries())
                <x-admin.card>
                    <x-slot:heading>Sezonlar</x-slot:heading>
                    <x-slot:actions>
                        <x-admin.button size="sm" icon="plus" wire:click="addSeason">Sezon ekle</x-admin.button>
                    </x-slot:actions>

                    @if ($seasonRows === [])
                        <x-admin.text>Sezon yok.</x-admin.text>
                    @else
                        <div class="space-y-3">
                            @foreach ($seasonRows as $id => $row)
                                <div wire:key="season-{{ $id }}" class="grid items-start gap-3 sm:grid-cols-[5rem_7rem_8rem_1fr_auto]">
                                    <p class="pt-2 font-bold">{{ $seasons[$id]?->number }}. sezon</p>
                                    <x-admin.input wire:model="seasonRows.{{ $id }}.episode_count" type="number" min="0" aria-label="Bölüm sayısı" placeholder="bölüm" />
                                    <x-admin.select wire:model="seasonRows.{{ $id }}.rating" aria-label="Sezon puanı" placeholder="puan yok" :options="$ratingOptions" />
                                    <x-admin.input wire:model="seasonRows.{{ $id }}.note" aria-label="Sezon notu" placeholder="kısa not (opsiyonel)" />
                                    <x-admin.button size="sm" variant="ghost" square icon="trash-2" class="mt-1" wire:click="deleteSeason({{ $id }})" wire:confirm="Bu sezon silinsin mi?" aria-label="Sezonu sil" />
                                </div>
                            @endforeach
                            <div class="flex justify-end">
                                <x-admin.button wire:click="saveSeasons" icon="save">Sezonları kaydet</x-admin.button>
                            </div>
                        </div>
                    @endif
                </x-admin.card>
            @endif

            <x-admin.card>
                <x-slot:heading>Bilgiler</x-slot:heading>

                <div class="grid gap-5 sm:grid-cols-2">
                    <x-admin.input wire:model="form.title" label="Ad (Türkçe)" />
                    <x-admin.input wire:model="form.original_title" label="Orijinal ad" />
                    <x-admin.input wire:model="form.creator" :label="$watchable->isSeries() ? 'Yaratıcı' : 'Yönetmen'" />
                    <div class="grid grid-cols-2 gap-3">
                        <x-admin.input wire:model="form.year" type="number" label="Yıl" />
                        <x-admin.input wire:model="form.runtime_minutes" type="number" label="Süre (dk)" />
                    </div>
                    <x-admin.combobox wire:model="form.genres" :options="[]" label="Türler" class="sm:col-span-2" />
                    <x-admin.textarea wire:model="form.overview" label="Özet" rows="4" class="sm:col-span-2" description="Yorum yoksa sayfada gösterilir." />
                    <x-admin.textarea wire:model="form.castText" label="Oyuncular" rows="5" mono class="sm:col-span-2" description="Her satıra bir kişi: Ad — Rol" />
                </div>
            </x-admin.card>
        </div>

        <div class="space-y-6">
            <x-admin.card>
                <x-slot:heading>Yayın</x-slot:heading>
                <x-slot:actions>
                    <x-admin.badge :color="$watchable->publicationState()->color()">{{ $watchable->publicationState()->label() }}</x-admin.badge>
                </x-slot:actions>

                <div class="space-y-4">
                    <x-admin.input wire:model="form.published_at" type="datetime-local" label="Yayın tarihi" description="Boş: taslak. Kayıt (izledim + puan) hemen açılabilir, yorum ayrıca." />
                    <div class="flex gap-2">
                        <x-admin.button size="sm" variant="subtle" wire:click="publishNow">Şimdi</x-admin.button>
                        <x-admin.button size="sm" variant="ghost" wire:click="$set('form.published_at', '')">Taslağa al</x-admin.button>
                    </div>
                    <x-admin.separator />
                    <x-admin.input wire:model="form.slug" label="Adres" :description="'/izlediklerim/'.$watchable->type->routeSegment().'/'.($form->slug ?: '…')" mono />
                    <x-admin.textarea wire:model="form.meta_description" label="SEO açıklaması" rows="2" description="Boşsa özetin başı kullanılır." />
                    <x-admin.button type="submit" variant="primary" class="w-full">Kaydet</x-admin.button>
                </div>
            </x-admin.card>

            <x-admin.card>
                <x-slot:heading>Afiş</x-slot:heading>
                <x-admin.file-upload name="poster" :preview="$watchable->posterUrl(480)" description="Vurgu rengi afişten bir kere hesaplanır." />

                <div class="mt-4 flex items-end gap-2">
                    <x-admin.input wire:model="accent" type="color" label="Vurgu rengi" class="flex-1 [&_input]:h-10 [&_input]:p-1" />
                    <x-admin.button size="sm" wire:click="saveAccent" class="mb-1">Uygula</x-admin.button>
                </div>
            </x-admin.card>

            <x-admin.card>
                <x-slot:heading>Sil</x-slot:heading>
                <x-admin.text>Günlükteki izlemeler ve sezonlar da silinir.</x-admin.text>
                <x-admin.modal.trigger name="delete-watchable">
                    <x-admin.button variant="danger" icon="trash-2" class="mt-4 w-full">Sil</x-admin.button>
                </x-admin.modal.trigger>
            </x-admin.card>
        </div>
    </form>

    <x-admin.modal name="delete-watchable" :heading="$watchable->title.' silinsin mi?'" description="Bu işlem geri alınamaz.">
        <x-slot:footer>
            <x-admin.modal.close><x-admin.button variant="ghost">Vazgeç</x-admin.button></x-admin.modal.close>
            <x-admin.button variant="danger" wire:click="delete" x-on:click="$el.closest('dialog').close()">Sil</x-admin.button>
        </x-slot:footer>
    </x-admin.modal>
</div>
