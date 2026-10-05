<?php

use App\Actions\Goals\MarkChainDay;
use App\Enums\ChainDayState;
use App\Enums\ChainPeriod;
use App\Enums\GoalKind;
use App\Enums\GoalMeasure;
use App\Enums\GoalVisibility;
use App\Enums\Section;
use App\Livewire\Forms\GoalForm;
use App\Models\Goal;
use App\Support\ChainReminders;
use App\Support\ChainStats;
use App\Support\Images\ImageStore;
use Carbon\CarbonImmutable;
use Illuminate\Database\Eloquent\Collection;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Layout;
use Livewire\Component;
use Livewire\WithFileUploads;

new #[Layout('layouts::admin')] class extends Component {
    use WithFileUploads;

    public GoalForm $form;

    /** @var mixed */
    public $image = null;

    public string $progressDate = '';

    public int $progressAmount = 1;

    public string $progressNote = '';

    public string $milestoneTitle = '';

    public string $updateDate = '';

    public string $updateBody = '';

    public function mount(?Goal $goal = null, ?string $kind = null): void
    {
        if ($goal?->exists) {
            $this->form->setGoal($goal);
        } else {
            $this->form->forKind(GoalKind::fromRouteSegment((string) $kind) ?? abort(404));
        }

        $this->progressDate = now()->toDateString();
        $this->updateDate = now()->toDateString();
    }

    public function save(): void
    {
        $isNew = $this->form->goal === null;
        $goal = $this->form->store();

        if ($isNew) {
            session()->flash('toast', ['text' => 'Hedef oluşturuldu.', 'variant' => 'success']);
            $this->redirectRoute('admin.goals.edit', $goal, navigate: true);

            return;
        }

        $this->dispatch('toast', text: 'Kaydedildi.');
    }

    public function cycleDay(string $date, MarkChainDay $markChainDay): void
    {
        $markChainDay->cycle($this->goal(), CarbonImmutable::parse($date));
        $this->goal()->unsetRelation('chainDays');
    }

    public function addProgress(): void
    {
        $validated = $this->validate([
            'progressDate' => ['required', 'date', 'before_or_equal:today'],
            'progressAmount' => ['required', 'integer', 'min:1', 'max:100000'],
            'progressNote' => ['nullable', 'string', 'max:160'],
        ], attributes: ['progressDate' => 'tarih', 'progressAmount' => 'miktar', 'progressNote' => 'not']);

        $this->goal()->progressEntries()->create([
            'date' => $validated['progressDate'],
            'amount' => $validated['progressAmount'],
            'note' => $validated['progressNote'] ?: null,
        ]);

        $this->reset('progressNote');
        $this->progressAmount = 1;
        $this->dispatch('toast', text: 'İlerleme eklendi.');
    }

    public function deleteProgress(int $id): void
    {
        $this->goal()->progressEntries()->findOrFail($id)->delete();
    }

    public function addMilestone(): void
    {
        $this->validate(['milestoneTitle' => ['required', 'string', 'max:160']], attributes: ['milestoneTitle' => 'kilometre taşı']);

        $goal = $this->goal();
        $goal->milestones()->create(['title' => $this->milestoneTitle, 'sort_order' => (int) $goal->milestones()->max('sort_order') + 1]);
        $this->reset('milestoneTitle');
    }

    public function toggleMilestone(int $id): void
    {
        $milestone = $this->goal()->milestones()->findOrFail($id);
        $milestone->update(['done_at' => $milestone->done_at ? null : now()]);
    }

    public function sortMilestone(int $id, int $position): void
    {
        $this->goal()->milestones()->findOrFail($id)->moveTo($position);
    }

    public function deleteMilestone(int $id): void
    {
        $this->goal()->milestones()->findOrFail($id)->delete();
    }

    public function toggleAchieved(): void
    {
        $goal = $this->goal();
        $goal->update(['achieved_at' => $goal->achieved_at ? null : now()]);
        $this->dispatch('toast', text: $goal->achieved_at ? 'BAŞARILDI damgası basıldı.' : 'Damga kaldırıldı.');
    }

    public function addUpdate(): void
    {
        $validated = $this->validate([
            'updateDate' => ['required', 'date', 'before_or_equal:today'],
            'updateBody' => ['required', 'string', 'max:5000'],
        ], attributes: ['updateDate' => 'tarih', 'updateBody' => 'güncelleme']);

        $this->goal()->updates()->create(['date' => $validated['updateDate'], 'body' => $validated['updateBody']]);
        $this->reset('updateBody');
        $this->dispatch('toast', text: 'Güncelleme eklendi.');
    }

    public function deleteUpdate(int $id): void
    {
        $this->goal()->updates()->findOrFail($id)->delete();
    }

    public function updatedImage(ImageStore $images): void
    {
        $this->validate(['image' => ['image', 'max:10240']], attributes: ['image' => 'görsel']);

        $goal = $this->goal();
        $old = $goal->image_path;
        $goal->forceFill(['image_path' => $images->store($this->image->getRealPath(), 'goals')])->save();
        $images->delete($old);
        $this->image = null;
    }

    public function removeImage(ImageStore $images): void
    {
        $goal = $this->goal();
        $images->delete($goal->image_path);
        $goal->forceFill(['image_path' => null])->save();
    }

    public function delete(): void
    {
        $this->goal()->delete();

        session()->flash('toast', ['text' => 'Hedef silindi.', 'variant' => 'success']);
        $this->redirectRoute('admin.goals.index', navigate: true);
    }

    /**
     * @return Collection<int, Goal>
     */
    #[Computed]
    public function parents(): Collection
    {
        return Goal::query()->ofKind(GoalKind::LongTerm)->orderBy('sort_order')->get();
    }

    private function goal(): Goal
    {
        return $this->form->goal ?? abort(404);
    }

    public function render(): mixed
    {
        return $this->view()->title(($this->form->goal?->title ?? 'Yeni '.mb_strtolower($this->form->kind()->label())).' · Hedefler');
    }
}; ?>

@php
    $goal = $form->goal;
    $kind = $form->kind();
    $visibilityOptions = collect(GoalVisibility::cases())->mapWithKeys(fn ($visibility) => [$visibility->value => $visibility->label()])->all();
    $today = CarbonImmutable::today();
    $history = $goal && $kind === GoalKind::Chain ? collect($goal->chainHistory())->keyBy(fn ($day) => $day['date']->toDateString()) : collect();
    // The grid shows days; the numbers count links (days, weeks or months).
    $states = $goal && $kind === GoalKind::Chain ? array_column($goal->chainLinks(), 'state') : [];
    $daily = ($goal?->chain_period ?? ChainPeriod::Day) === ChainPeriod::Day;
    $unit = $goal?->chain_period->unit() ?? 'gün';
    $currentPeriod = $goal && $kind === GoalKind::Chain && ! $daily ? $goal->chainPeriodAt() : null;
    $gridStart = $today->startOfYear()->subDays($today->startOfYear()->dayOfWeekIso - 1);
    $weekCount = (int) ceil(($gridStart->diffInDays($today->endOfYear()->startOfDay()) + 1) / 7);
    $publicUrl = match (true) {
        $goal === null => null,
        $kind === GoalKind::Chain => route('goals.chain', $goal->slug),
        $kind === GoalKind::LongTerm => route('goals.show', $goal->slug),
        default => route('goals.index').'#hedef-'.$goal->slug,
    };
@endphp

<div class="space-y-6">
    <x-admin.page-header :heading="$goal?->title ?? 'Yeni '.mb_strtolower($kind->label())" :description="$kind->label()" :dot="Section::Goals->adminDotClass()">
        <x-slot:actions>
            <x-admin.button :href="route('admin.goals.index')" icon="arrow-left" variant="ghost" wire:navigate>Hedefler</x-admin.button>
            @if ($publicUrl && $goal->visibility !== GoalVisibility::Hidden)
                <x-admin.button :href="$publicUrl" icon="external-link" target="_blank">Sitede gör</x-admin.button>
            @endif
        </x-slot:actions>
    </x-admin.page-header>

    <form wire:submit="save" class="grid gap-6 xl:grid-cols-[1fr_20rem]">
        <div class="min-w-0 space-y-6">
            <x-admin.card>
                <div class="grid gap-5 sm:grid-cols-2">
                    <x-admin.input wire:model="form.title" label="Başlık" class="sm:col-span-2" />

                    @if ($kind !== GoalKind::LongTerm)
                        <x-admin.select wire:model="form.parent_id" label="Üst hedef" placeholder="Yok" :options="$this->parents->mapWithKeys(fn ($parent) => [$parent->id => $parent->title])->all()" description="Sadece uzun vadeli bir hedef olabilir." class="sm:col-span-2" />
                    @endif

                    @if ($kind === GoalKind::Chain)
                        <x-admin.input wire:model="form.started_on" type="date" label="Başlangıç" />
                        <x-admin.input wire:model="form.ended_on" type="date" label="Bitiş" description="Bıraktığın zincir silinmez, sitede listeden düşer." />
                        <x-admin.select wire:model.live="form.chain_period" label="Birim" :options="collect(ChainPeriod::cases())->mapWithKeys(fn ($period) => [$period->value => $period->label()])->all()" description="Günleri yine tek tek işaretlersin; haftalık ve aylık zincirde her halka bir dönem." />
                        @if ($form->chain_period !== ChainPeriod::Day->value)
                            <x-admin.input wire:model="form.chain_target" type="number" min="1" :max="ChainPeriod::from($form->chain_period)->maxTarget()" :label="$form->chain_period === ChainPeriod::Week->value ? 'Haftada kaç kez' : 'Ayda kaç kez'" description="Mazeretli günler de sayılır. Günler azalınca sabah 08:00'de sana e-posta gelir." />
                        @else
                            <x-admin.text class="self-end pb-2">Her gün işaretlenmezse akşam 20:00'de sana hatırlatma gelir.</x-admin.text>
                        @endif
                    @elseif ($kind === GoalKind::Yearly)
                        <x-admin.input wire:model="form.year" type="number" label="Yıl" />
                        <x-admin.select wire:model.live="form.measure" label="Ölçü" :options="collect(GoalMeasure::cases())->mapWithKeys(fn ($measure) => [$measure->value => $measure->label()])->all()" />
                        @if ($form->measure === GoalMeasure::Numeric->value)
                            <x-admin.input wire:model="form.target" type="number" min="1" label="Hedef sayı" />
                            <x-admin.input wire:model="form.unit" label="Birim" placeholder="kitap, km, yazı…" />
                            <x-admin.switch wire:model="form.show_progress_notes" label="İlerleme notlarını sitede göster" description="ör. okuduğun kitapların adları" class="sm:col-span-2" />
                        @endif
                    @else
                        <x-admin.input wire:model="form.started_year" type="number" label="Başlangıç yılı" />
                        <div></div>
                        <x-admin.markdown wire:model="form.why" section="goals" rows="5" label="Neden önemli?" class="sm:col-span-2" />
                    @endif
                </div>
            </x-admin.card>

            @if ($goal && $kind === GoalKind::Chain)
                <x-admin.card>
                    <x-slot:heading>{{ $today->year }} ızgarası</x-slot:heading>
                    <x-slot:actions>
                        <span class="font-mono text-sm">🔥 {{ ChainStats::currentStreak($states) }} {{ $unit }} · en uzun {{ ChainStats::bestStreak($states) }} · %{{ ChainStats::successRate($states) }}</span>
                    </x-slot:actions>

                    <x-admin.text class="mb-4">
                        Bir güne tıkla: {{ $daily ? 'kopuk' : 'boş' }} → tamam → mazeretli → {{ $daily ? 'kopuk' : 'boş' }}. Gelecek kilitli.
                        @if ($currentPeriod)
                            <span @class(['font-semibold', 'text-red-600 dark:text-red-400' => ChainReminders::isDue($currentPeriod)])>
                                {{ ChainReminders::progress($goal, $currentPeriod) }}{{ $currentPeriod['needed'] > 0 ? ', '.$currentPeriod['daysLeft'].' günde '.$currentPeriod['needed'].' kez daha' : ' ✓' }}
                            </span>
                        @endif
                    </x-admin.text>
                    <x-admin.error :message="$errors->first('date')" class="mb-3" />

                    {{-- One column per week (Monday first), months on top. The vertical padding keeps today's ring inside the scroller, which clips. --}}
                    <div class="relative overflow-x-auto py-1 pr-1 pb-3" x-data x-init="const today = $el.querySelector('[data-today]'); if (today) $el.scrollLeft = today.offsetLeft - $el.clientWidth / 2">
                        <div class="inline-flex flex-col gap-1">
                            <div class="flex gap-1" aria-hidden="true">
                                <span class="sticky left-0 z-10 w-8 shrink-0 bg-white dark:bg-zinc-900"></span>
                                @for ($week = 0; $week < $weekCount; $week++)
                                    @php
                                        // A month is named over the week its first Sunday ends.
                                        $weekEnd = $gridStart->addWeeks($week)->addDays(6);
                                    @endphp
                                    <span class="w-3.5 overflow-visible text-[10px] leading-none whitespace-nowrap text-zinc-500">
                                        {{ $weekEnd->day <= 7 && $weekEnd->year === $today->year ? $weekEnd->locale('tr')->translatedFormat('M') : '' }}
                                    </span>
                                @endfor
                            </div>

                            <div class="flex gap-1">
                                {{-- Sticky: the scroller opens at today, the weekday names stay in view. --}}
                                <div class="sticky left-0 z-10 grid w-8 grid-rows-7 gap-1 bg-white text-[10px] leading-3.5 text-zinc-500 dark:bg-zinc-900" aria-hidden="true">
                                    <span>Pzt</span><span></span><span>Çar</span><span></span><span>Cum</span><span></span><span>Paz</span>
                                </div>

                                @for ($week = 0; $week < $weekCount; $week++)
                                    <div class="grid grid-rows-7 gap-1">
                                        @for ($weekday = 0; $weekday < 7; $weekday++)
                                            @php
                                                $day = $gridStart->addWeeks($week)->addDays($weekday);
                                                $key = $day->toDateString();
                                                $state = $history->get($key)['state'] ?? null;
                                                $locked = $day->isFuture() || $day->year !== $today->year || ($goal->started_on && $day->lessThan($goal->started_on)) || ($goal->ended_on && $day->greaterThan($goal->ended_on));
                                            @endphp
                                            @if ($day->year !== $today->year)
                                                <span class="size-3.5"></span>
                                            @else
                                                <button
                                                    type="button"
                                                    wire:key="day-{{ $key }}"
                                                    @if ($day->isToday()) data-today @endif
                                                    @if ($locked) disabled @else wire:click="cycleDay('{{ $key }}')" @endif
                                                    title="{{ $day->locale('tr')->translatedFormat('j F l') }}: {{ ['done' => 'tamam', 'excused' => 'mazeretli', 'missed' => $daily ? 'kopuk' : 'boş'][$state] ?? ($day->isToday() ? 'bugün, henüz işaretlenmedi' : '—') }}"
                                                    aria-label="{{ $day->locale('tr')->translatedFormat('j F') }}"
                                                    @class([
                                                        'size-3.5 rounded-[3px] transition',
                                                        'cursor-pointer hover:ring-2 hover:ring-accent/40' => ! $locked,
                                                        'bg-section-goals' => $state === 'done',
                                                        'bg-amber-300 dark:bg-amber-500/60' => $state === 'excused',
                                                        'bg-zinc-200 dark:bg-zinc-700' => $state === 'missed' || ($state === null && ! $locked),
                                                        'bg-zinc-100 dark:bg-zinc-800/50' => $locked,
                                                        'ring-2 ring-accent' => $day->isToday(),
                                                    ])
                                                ></button>
                                            @endif
                                        @endfor
                                    </div>
                                @endfor
                            </div>
                        </div>
                    </div>
                    <div class="mt-3 flex flex-wrap gap-4 text-xs text-zinc-500">
                        <span class="flex items-center gap-1.5"><span class="size-3 rounded-[3px] bg-section-goals"></span> tamam</span>
                        <span class="flex items-center gap-1.5"><span class="size-3 rounded-[3px] bg-amber-300"></span> mazeretli</span>
                        <span class="flex items-center gap-1.5"><span class="size-3 rounded-[3px] bg-zinc-200 dark:bg-zinc-700"></span> {{ $daily ? 'kopuk' : 'boş' }}</span>
                    </div>
                </x-admin.card>
            @endif

            @if ($goal && $kind === GoalKind::Yearly)
                @switch($goal->measure)
                    @case(GoalMeasure::Numeric)
                        <x-admin.card>
                            <x-slot:heading>İlerleme · {{ $goal->current() }} / {{ $goal->target }} {{ $goal->unit }}</x-slot:heading>

                            <div class="grid gap-3 sm:grid-cols-[10rem_6rem_1fr_auto] sm:items-start">
                                <x-admin.input wire:model="progressDate" type="date" aria-label="Tarih" />
                                <x-admin.input wire:model="progressAmount" type="number" min="1" aria-label="Miktar" />
                                <x-admin.input wire:model="progressNote" aria-label="Not" placeholder="not (ör. Tutunamayanlar)" />
                                <x-admin.button icon="plus" wire:click="addProgress">Ekle</x-admin.button>
                            </div>

                            @if ($goal->progressEntries->isNotEmpty())
                                <ul class="mt-4 divide-y divide-zinc-100 rounded-lg border border-zinc-200 text-sm dark:divide-zinc-800 dark:border-zinc-700">
                                    @foreach ($goal->progressEntries as $entry)
                                        <li wire:key="progress-{{ $entry->id }}" class="flex items-center gap-3 px-3 py-2">
                                            <span class="w-24 font-mono text-xs text-zinc-500">{{ $entry->date->format('d.m.Y') }}</span>
                                            <span class="w-12 font-mono">+{{ $entry->amount }}</span>
                                            <span class="flex-1 text-zinc-600 dark:text-zinc-400">{{ $entry->note }}</span>
                                            <x-admin.button size="sm" variant="ghost" square icon="trash-2" wire:click="deleteProgress({{ $entry->id }})" wire:confirm="Bu kayıt silinsin mi?" aria-label="Sil" />
                                        </li>
                                    @endforeach
                                </ul>
                            @endif
                        </x-admin.card>
                        @break

                    @case(GoalMeasure::Milestones)
                        <x-admin.card>
                            <x-slot:heading>Kilometre taşları</x-slot:heading>

                            @if ($goal->milestones->isNotEmpty())
                                <ul wire:sort="sortMilestone" class="mb-4 space-y-2">
                                    @foreach ($goal->milestones as $milestone)
                                        <li wire:key="milestone-{{ $milestone->id }}" wire:sort:item="{{ $milestone->id }}" class="flex items-center gap-3 rounded-lg border border-zinc-200 px-3 py-2 dark:border-zinc-700">
                                            <button type="button" wire:sort:handle class="cursor-grab text-zinc-400" aria-label="Sırasını değiştir"><x-admin.icon name="grip-vertical" /></button>
                                            <input type="checkbox" class="size-4 accent-accent" @checked($milestone->done_at) wire:click="toggleMilestone({{ $milestone->id }})" aria-label="{{ $milestone->title }}: tamamlandı" />
                                            <span @class(['flex-1', 'text-zinc-400 line-through' => $milestone->done_at])>{{ $milestone->title }}</span>
                                            <x-admin.button size="sm" variant="ghost" square icon="trash-2" wire:click="deleteMilestone({{ $milestone->id }})" wire:confirm="Bu taş silinsin mi?" aria-label="Sil" />
                                        </li>
                                    @endforeach
                                </ul>
                            @endif

                            <div class="flex items-start gap-2">
                                <x-admin.input wire:model="milestoneTitle" placeholder="Yeni kilometre taşı" aria-label="Yeni kilometre taşı" class="flex-1" wire:keydown.enter.prevent="addMilestone" />
                                <x-admin.button icon="plus" wire:click="addMilestone">Ekle</x-admin.button>
                            </div>
                        </x-admin.card>
                        @break

                    @default
                        <x-admin.card>
                            <x-slot:heading>Durum</x-slot:heading>
                            <div class="flex items-center justify-between gap-4">
                                <x-admin.text>{{ $goal->achieved_at ? $goal->achieved_at->locale('tr')->translatedFormat('j F Y').' tarihinde başarıldı.' : 'Henüz başarılmadı.' }}</x-admin.text>
                                <x-admin.button :variant="$goal->achieved_at ? 'outline' : 'primary'" wire:click="toggleAchieved">{{ $goal->achieved_at ? 'Damgayı kaldır' : 'BAŞARILDI' }}</x-admin.button>
                            </div>
                        </x-admin.card>
                @endswitch
            @endif

            @if ($goal && $kind === GoalKind::LongTerm)
                <x-admin.card>
                    <x-slot:heading>Güncellemeler</x-slot:heading>
                    <div class="space-y-3">
                        <div class="grid gap-3 sm:grid-cols-[10rem_1fr]">
                            <x-admin.input wire:model="updateDate" type="date" label="Tarih" />
                            <x-admin.textarea wire:model="updateBody" label="Ne oldu?" rows="3" placeholder="Markdown" />
                        </div>
                        <div class="flex justify-end"><x-admin.button icon="plus" wire:click="addUpdate">Güncelleme ekle</x-admin.button></div>
                        @if ($goal->updates->isNotEmpty())
                            <ol class="divide-y divide-zinc-100 rounded-lg border border-zinc-200 text-sm dark:divide-zinc-800 dark:border-zinc-700">
                                @foreach ($goal->updates as $update)
                                    <li wire:key="update-{{ $update->id }}" class="flex items-start gap-3 p-3">
                                        <span class="w-24 shrink-0 font-mono text-xs text-zinc-500">{{ $update->date->format('d.m.Y') }}</span>
                                        <p class="flex-1">{{ \Illuminate\Support\Str::limit($update->body, 160) }}</p>
                                        <x-admin.button size="sm" variant="ghost" square icon="trash-2" wire:click="deleteUpdate({{ $update->id }})" wire:confirm="Bu güncelleme silinsin mi?" aria-label="Sil" />
                                    </li>
                                @endforeach
                            </ol>
                        @endif
                    </div>
                </x-admin.card>

                @if ($goal->children->isNotEmpty())
                    <x-admin.card>
                        <x-slot:heading>Bu hedefe bağlı olanlar</x-slot:heading>
                        <ul class="space-y-1 text-sm">
                            @foreach ($goal->children as $child)
                                <li><x-admin.link :href="route('admin.goals.edit', $child)">{{ $child->title }}</x-admin.link> <span class="text-zinc-500">· {{ $child->kind->label() }}{{ $child->year ? ' '.$child->year : '' }}</span></li>
                            @endforeach
                        </ul>
                    </x-admin.card>
                @endif
            @endif
        </div>

        <div class="space-y-6">
            <x-admin.card>
                <x-slot:heading>Görünürlük</x-slot:heading>
                <div class="space-y-4">
                    <x-admin.select wire:model="form.visibility" :options="$visibilityOptions" aria-label="Görünürlük" description="Açık: herkes. Sansürlü: kart durur, metin karalanır (Yakın rolü okur). Gizli: sadece sen." />
                    @if ($kind !== GoalKind::Yearly)
                        <x-admin.input wire:model="form.slug" label="Adres" mono description="Boşsa başlıktan üretilir. Sansürlüyken ziyaretçi bu adresi görmez." />
                    @endif
                    <x-admin.button type="submit" variant="primary" class="w-full">Kaydet</x-admin.button>
                </div>
            </x-admin.card>

            @if ($goal && $kind === GoalKind::LongTerm)
                <x-admin.card>
                    <x-slot:heading>Görsel</x-slot:heading>
                    <x-admin.file-upload name="image" :preview="$goal->imageUrl(480)" description="Opsiyonel; panodaki kartta ve hedef sayfasında." />
                    @if ($goal->image_path)
                        <x-admin.button size="sm" variant="ghost" icon="trash-2" class="mt-2" wire:click="removeImage">Görseli kaldır</x-admin.button>
                    @endif
                </x-admin.card>
            @endif

            @if ($goal)
                <x-admin.card>
                    <x-slot:heading>Sil</x-slot:heading>
                    <x-admin.text>{{ $kind === GoalKind::LongTerm ? 'Bağlı hedefler silinmez, üst hedefsiz kalır.' : 'Geçmişi de silinir. Tutmayan bir hedefi silmek yerine arşivde bırakabilirsin.' }}</x-admin.text>
                    <x-admin.modal.trigger name="delete-goal">
                        <x-admin.button variant="danger" icon="trash-2" class="mt-4 w-full">Sil</x-admin.button>
                    </x-admin.modal.trigger>
                </x-admin.card>
            @endif
        </div>
    </form>

    @if ($goal)
        <x-admin.modal name="delete-goal" :heading="$goal->title.' silinsin mi?'" description="Bu işlem geri alınamaz.">
            <x-slot:footer>
                <x-admin.modal.close><x-admin.button variant="ghost">Vazgeç</x-admin.button></x-admin.modal.close>
                <x-admin.button variant="danger" wire:click="delete" x-on:click="$el.closest('dialog').close()">Sil</x-admin.button>
            </x-slot:footer>
        </x-admin.modal>
    @endif
</div>
