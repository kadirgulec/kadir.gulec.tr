<?php

use App\Enums\ReviewItemKind;
use App\Enums\ReviewItemOutcome;
use App\Enums\Section;
use App\Models\MonthlyReview;
use App\Models\ReviewItem;
use App\Support\Content\ReviewContent;
use App\Support\FormControl;
use App\Support\Reviews\ReviewSuggestions;
use App\Support\TurkishDate;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Validation\Rule;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Locked;
use Livewire\Component;

/*
 * One month's review: summary, score, the three lists, last month's "try"
 * items, the number tiles and publishing. Lists, outcomes and tiles are saved
 * at once (like a goal's milestones); summary, score and date with "Kaydet".
 */
new #[Layout('layouts::admin')] class extends Component {
    #[Locked]
    public int $reviewId;

    public string $summary = '';

    public string $score = '';

    public string $published_at = '';

    /** @var array<string, string> New line per kind, typed under each list. */
    public array $newItems = ['good' => '', 'hard' => '', 'try' => ''];

    #[Locked]
    public ?int $editingId = null;

    public string $editingBody = '';

    public function mount(MonthlyReview $review): void
    {
        $this->reviewId = $review->id;
        $this->summary = (string) $review->summary;
        $this->score = $review->score !== null ? (string) $review->score : '';
        $this->published_at = FormControl::dateTimeLocal($review->published_at);
    }

    #[Computed]
    public function review(): MonthlyReview
    {
        return MonthlyReview::query()->with('items')->findOrFail($this->reviewId);
    }

    #[Computed]
    public function previousReview(): ?MonthlyReview
    {
        return $this->review->previousReview();
    }

    /**
     * Last month's "try" items, whose outcome this review records.
     *
     * @return Collection<int, ReviewItem>
     */
    #[Computed]
    public function previousTries(): Collection
    {
        return $this->previousReview?->items()->where('kind', ReviewItemKind::Try)->get() ?? new Collection;
    }

    /**
     * Suggestions not taken yet (a line already in the review is left out).
     *
     * @return list<array{kind: ReviewItemKind, text: string}>
     */
    #[Computed]
    public function suggestions(): array
    {
        $taken = $this->review->items->pluck('body')->map(fn (string $body): string => mb_strtolower($body))->all();

        return array_values(array_filter(
            app(ReviewSuggestions::class)->for($this->review),
            fn (array $suggestion): bool => ! in_array(mb_strtolower($suggestion['text']), $taken, true),
        ));
    }

    /**
     * Every tile, hidden ones included.
     *
     * @return list<array<string, mixed>>
     */
    #[Computed]
    public function tiles(): array
    {
        return app(ReviewContent::class)->tiles($this->review, withHidden: true);
    }

    public function save(): void
    {
        $validated = $this->validate([
            'summary' => ['nullable', 'string', 'max:255'],
            'score' => ['nullable', 'integer', 'between:1,10'],
            'published_at' => ['nullable', 'date'],
        ], attributes: ['summary' => 'özet', 'score' => 'puan', 'published_at' => 'yayın tarihi']);

        $this->review->update([
            'summary' => filled($validated['summary']) ? trim($validated['summary']) : null,
            'score' => filled($validated['score']) ? (int) $validated['score'] : null,
            'published_at' => FormControl::parseDateTimeLocal($this->published_at),
        ]);

        $this->dispatch('toast', text: 'Kaydedildi.');
    }

    public function publishNow(): void
    {
        $this->published_at = FormControl::dateTimeLocal(now());
    }

    public function addItem(string $kind): void
    {
        $kind = ReviewItemKind::tryFrom($kind) ?? abort(404);
        $this->validate(['newItems.'.$kind->value => ['required', 'string', 'max:500']], attributes: ['newItems.'.$kind->value => 'madde']);

        $this->createItem($kind, $this->newItems[$kind->value]);
        $this->newItems[$kind->value] = '';
    }

    /**
     * Takes a suggestion into the list it was meant for, or into $kind.
     */
    public function takeSuggestion(int $index, ?string $kind = null): void
    {
        $suggestion = $this->suggestions[$index] ?? null;

        abort_if($suggestion === null, 404);

        $this->createItem($kind !== null ? (ReviewItemKind::tryFrom($kind) ?? abort(404)) : $suggestion['kind'], $suggestion['text']);
    }

    public function startEditing(int $id): void
    {
        $this->editingId = $id;
        $this->editingBody = $this->item($id)->body;
    }

    public function updateItem(): void
    {
        abort_if($this->editingId === null, 404);

        $this->validate(['editingBody' => ['required', 'string', 'max:500']], attributes: ['editingBody' => 'madde']);

        $this->item($this->editingId)->update(['body' => trim($this->editingBody)]);
        $this->reset('editingId', 'editingBody');
        unset($this->review, $this->suggestions);
    }

    public function sortItem(int $id, int $position): void
    {
        $this->item($id)->moveTo($position);
        unset($this->review);
    }

    public function deleteItem(int $id): void
    {
        $this->item($id)->delete();
        unset($this->review, $this->suggestions);
    }

    /**
     * Records how one of last month's "try" items went; null clears it.
     */
    public function setOutcome(int $id, ?string $outcome): void
    {
        $item = $this->previousTries->firstWhere('id', $id);

        abort_if($item === null, 404);

        $item->update(['outcome' => $outcome !== null ? (ReviewItemOutcome::tryFrom($outcome) ?? abort(404)) : null]);
        unset($this->previousTries);
    }

    public function toggleTile(string $key): void
    {
        abort_unless(in_array($key, array_column($this->tiles, 'key'), true), 404);

        $hidden = $this->review->hidden_stats ?? [];
        $hidden = in_array($key, $hidden, true) ? array_values(array_diff($hidden, [$key])) : [...$hidden, $key];

        $this->review->update(['hidden_stats' => $hidden === [] ? null : $hidden]);
        unset($this->review, $this->tiles);
    }

    /**
     * Works the month's numbers out again, e.g. after a chain day was corrected.
     */
    public function refreshStats(): void
    {
        $review = $this->review;
        $review->refreshStats();
        $review->save();

        unset($this->review, $this->tiles, $this->suggestions);
        $this->dispatch('toast', text: 'Rakamlar yeniden hesaplandı.');
    }

    public function delete(): void
    {
        $this->review->delete();

        session()->flash('toast', ['text' => 'Değerlendirme silindi.', 'variant' => 'success']);
        $this->redirectRoute('admin.reviews.index', navigate: true);
    }

    private function createItem(ReviewItemKind $kind, string $body): void
    {
        $item = new ReviewItem(['kind' => $kind, 'body' => trim($body)]);
        $item->review()->associate($this->review);
        $item->sort_order = $item->nextSortOrder();
        $item->save();

        unset($this->review, $this->suggestions);
    }

    private function item(int $id): ReviewItem
    {
        return $this->review->items()->findOrFail($id);
    }

    public function render(): mixed
    {
        return $this->view()->title(TurkishDate::monthYear($this->review->month).' · Aylık değerlendirme');
    }
}; ?>

@php
    $review = $this->review;
    $lists = [
        ['kind' => ReviewItemKind::Good, 'heading' => '+ İyi giden', 'placeholder' => 'Bu ay ne iyi gitti?'],
        ['kind' => ReviewItemKind::Hard, 'heading' => '– Zorlandığım', 'placeholder' => 'Nerede zorlandın?'],
        ['kind' => ReviewItemKind::Try, 'heading' => '→ '.TurkishDate::inMonth($review->month->addMonth()).' deneyeceğim', 'placeholder' => 'Gelecek ay ne deneyeceksin?'],
    ];
@endphp

<div class="space-y-6">
    <x-admin.page-header :heading="TurkishDate::monthYear($review->month)" description="Rakamlar dondurulmuş; ne iyi gitti, nerede zorlandın, gelecek ay ne deneyeceksin." :dot="Section::Goals->adminDotClass()">
        <x-slot:actions>
            <x-admin.button :href="route('admin.reviews.index')" icon="arrow-left" variant="ghost" wire:navigate>Aylık değerlendirme</x-admin.button>
            @if (Route::has('goals.reviews.show'))
                <x-admin.button :href="route('goals.reviews.show', $review->monthKey())" icon="external-link" target="_blank">{{ $review->isPublished() ? 'Sitede gör' : 'Önizle' }}</x-admin.button>
            @endif
        </x-slot:actions>
    </x-admin.page-header>

    <div class="grid gap-6 xl:grid-cols-[1fr_22rem]">
        <div class="min-w-0 space-y-6">
            <x-admin.card>
                <x-slot:heading>Ayın özeti</x-slot:heading>
                <form wire:submit="save" class="grid gap-4 sm:grid-cols-[1fr_8rem]">
                    <x-admin.input wire:model="summary" label="Tek cümle" placeholder="Spor rutini oturdu, yazı tarafı yine aksadı." description="İsteğe bağlı. Boşsa sitede görünmez." />
                    <x-admin.input wire:model="score" type="number" min="1" max="10" label="Puan" description="1–10, isteğe bağlı." />
                </form>
            </x-admin.card>

            @foreach ($lists as $list)
                @php($items = $review->items->where('kind', $list['kind']))
                <x-admin.card>
                    <x-slot:heading>{{ $list['heading'] }}</x-slot:heading>

                    @if ($items->isNotEmpty())
                        <ul wire:sort="sortItem" class="mb-4 space-y-2">
                            @foreach ($items as $item)
                                <li wire:key="item-{{ $item->id }}" wire:sort:item="{{ $item->id }}" class="flex items-center gap-3 rounded-lg border border-zinc-200 px-3 py-2 dark:border-zinc-700">
                                    <button type="button" wire:sort:handle class="cursor-grab text-zinc-400" aria-label="Sırasını değiştir"><x-admin.icon name="grip-vertical" /></button>
                                    @if ($editingId === $item->id)
                                        <x-admin.input wire:model="editingBody" aria-label="Maddeyi düzenle" class="flex-1" wire:keydown.enter.prevent="updateItem" wire:keydown.escape="$set('editingId', null)" />
                                        <x-admin.button size="sm" variant="ghost" square icon="check" wire:click="updateItem" aria-label="Kaydet" />
                                    @else
                                        <span class="min-w-0 flex-1 [&_p]:m-0">{{ new \Illuminate\Support\HtmlString((string) $item->body_html) }}</span>
                                        <x-admin.button size="sm" variant="ghost" square icon="pencil" wire:click="startEditing({{ $item->id }})" aria-label="Düzenle" />
                                        <x-admin.button size="sm" variant="ghost" square icon="trash-2" wire:click="deleteItem({{ $item->id }})" wire:confirm="Bu madde silinsin mi?" aria-label="Sil" />
                                    @endif
                                </li>
                            @endforeach
                        </ul>
                    @endif

                    <div class="flex gap-2">
                        <x-admin.input wire:model="newItems.{{ $list['kind']->value }}" :placeholder="$list['placeholder']" :aria-label="$list['heading'].': yeni madde'" class="flex-1" wire:keydown.enter.prevent="addItem('{{ $list['kind']->value }}')" />
                        <x-admin.button icon="plus" wire:click="addItem('{{ $list['kind']->value }}')">Ekle</x-admin.button>
                    </div>
                    <p class="mt-1.5 text-xs text-zinc-500">Kalın, eğik ve link yazılabilir (Markdown).</p>
                </x-admin.card>
            @endforeach

            @if ($this->suggestions !== [])
                <x-admin.card>
                    <x-slot:heading>Rakamlardan öneriler</x-slot:heading>
                    <p class="mb-3 text-sm text-zinc-500">Kaydedilmez; eklediğin öneri listeden düşer. Sansürlü hedeflerin adları burada açık yazılır, sitede karalanır.</p>
                    <ul class="space-y-2">
                        @foreach ($this->suggestions as $index => $suggestion)
                            <li wire:key="suggestion-{{ md5($suggestion['text']) }}" class="flex flex-wrap items-center gap-2 rounded-lg bg-zinc-50 px-3 py-2 dark:bg-zinc-800/60">
                                <span class="min-w-0 flex-1 text-sm"><span class="font-bold text-accent">{{ $suggestion['kind']->sign() }}</span> {{ $suggestion['text'] }}</span>
                                <x-admin.button size="sm" variant="subtle" wire:click="takeSuggestion({{ $index }}, 'good')">İyi gidenlere</x-admin.button>
                                <x-admin.button size="sm" variant="subtle" wire:click="takeSuggestion({{ $index }}, 'hard')">Zorlandıklarıma</x-admin.button>
                            </li>
                        @endforeach
                    </ul>
                </x-admin.card>
            @endif

            @if ($this->previousTries->isNotEmpty())
                <x-admin.card>
                    <x-slot:heading>{{ TurkishDate::inMonth($review->month) }} denediklerim</x-slot:heading>
                    <p class="mb-3 text-sm text-zinc-500">Geçen ayın "deneyeceğim" maddeleri. Sitede yapılanlar işaretli, olmayanların üstü karalı görünür.</p>
                    <ul class="space-y-2">
                        @foreach ($this->previousTries as $try)
                            <li wire:key="try-{{ $try->id }}" class="flex flex-wrap items-center gap-3 rounded-lg border border-zinc-200 px-3 py-2 dark:border-zinc-700">
                                <span @class(['min-w-0 flex-1 [&_p]:m-0', 'text-zinc-400 line-through' => $try->outcome === ReviewItemOutcome::NotDone])>{{ new \Illuminate\Support\HtmlString((string) $try->body_html) }}</span>
                                <div class="flex gap-1" role="group" aria-label="Nasıl gitti?">
                                    <x-admin.button size="sm" :variant="$try->outcome === ReviewItemOutcome::Done ? 'primary' : 'ghost'" icon="check" wire:click="setOutcome({{ $try->id }}, '{{ ReviewItemOutcome::Done->value }}')" :aria-pressed="$try->outcome === ReviewItemOutcome::Done ? 'true' : 'false'">yaptım</x-admin.button>
                                    <x-admin.button size="sm" :variant="$try->outcome === ReviewItemOutcome::NotDone ? 'danger' : 'ghost'" icon="x" wire:click="setOutcome({{ $try->id }}, '{{ ReviewItemOutcome::NotDone->value }}')" :aria-pressed="$try->outcome === ReviewItemOutcome::NotDone ? 'true' : 'false'">olmadı</x-admin.button>
                                    @if ($try->outcome !== null)
                                        <x-admin.button size="sm" variant="ghost" square icon="rotate-ccw" wire:click="setOutcome({{ $try->id }}, null)" aria-label="İşareti kaldır" />
                                    @endif
                                </div>
                            </li>
                        @endforeach
                    </ul>
                </x-admin.card>
            @endif
        </div>

        <div class="space-y-6">
            <x-admin.card>
                <x-slot:heading>Yayın</x-slot:heading>
                <x-slot:actions>
                    <x-admin.badge :color="$review->publicationState()->color()">{{ $review->publicationState()->label() }}</x-admin.badge>
                </x-slot:actions>

                <form wire:submit="save" class="space-y-4">
                    <x-admin.input wire:model="published_at" type="datetime-local" label="Tarih" description="Boş: taslak. Gelecekte: o an kendiliğinden yayınlanır." />
                    <div class="flex gap-2">
                        <x-admin.button size="sm" variant="subtle" wire:click="publishNow">Şimdi</x-admin.button>
                        <x-admin.button size="sm" variant="ghost" wire:click="$set('published_at', '')">Taslağa al</x-admin.button>
                    </div>
                    <x-admin.button type="submit" variant="primary" class="w-full">Kaydet</x-admin.button>
                    <p class="text-xs text-zinc-500">Özet, puan ve tarih bu düğmeyle kaydedilir; maddeler ve işaretler anında.</p>
                </form>
            </x-admin.card>

            <x-admin.card>
                <x-slot:heading>Rakam kutuları</x-slot:heading>
                <ul class="space-y-3">
                    @foreach ($this->tiles as $tile)
                        <li wire:key="tile-{{ $tile['key'] }}">
                            <x-admin.switch
                                :checked="! in_array($tile['key'], $review->hidden_stats ?? [], true)"
                                wire:click="toggleTile('{{ $tile['key'] }}')"
                                :id="'tile-'.\Illuminate\Support\Str::slug($tile['key'])"
                                :label="$tile['value'].' '.$tile['unit'].($tile['label'] ? ' · '.$tile['label'] : '')"
                                :description="$tile['note']"
                            />
                        </li>
                    @endforeach
                    <li>
                        <x-admin.switch
                            :checked="! in_array('topPost', $review->hidden_stats ?? [], true)"
                            wire:click="toggleTile('topPost')"
                            id="tile-top-post"
                            label="Ayın en çok okunan yazısı"
                        />
                    </li>
                </ul>
                <x-admin.separator class="my-4" />
                <x-admin.button icon="refresh-cw" class="w-full" wire:click="refreshStats" wire:confirm="Rakamlar sitedeki kayıtlardan yeniden hesaplanacak. Devam edilsin mi?">Rakamları yeniden hesapla</x-admin.button>
                <p class="mt-1.5 text-xs text-zinc-500">Son hesap: {{ $review->updated_at?->format('d.m.Y H:i') }}. Silinen ziyaret kayıtları (13 aydan eski) geri gelmez.</p>
            </x-admin.card>

            <x-admin.card>
                <x-slot:heading>Sil</x-slot:heading>
                <x-admin.modal.trigger name="delete-review">
                    <x-admin.button variant="danger" icon="trash-2" class="w-full">Değerlendirmeyi sil</x-admin.button>
                </x-admin.modal.trigger>
            </x-admin.card>
        </div>
    </div>

    <x-admin.modal name="delete-review" :heading="TurkishDate::monthYear($review->month).' değerlendirmesi silinsin mi?'" description="Maddeleri de silinir. Bu işlem geri alınamaz.">
        <x-slot:footer>
            <x-admin.modal.close><x-admin.button variant="ghost">Vazgeç</x-admin.button></x-admin.modal.close>
            <x-admin.button variant="danger" wire:click="delete" x-on:click="$el.closest('dialog').close()">Sil</x-admin.button>
        </x-slot:footer>
    </x-admin.modal>
</div>
