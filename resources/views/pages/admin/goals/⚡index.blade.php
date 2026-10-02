<?php

use App\Actions\Goals\CopyYearlyGoals;
use App\Enums\GoalKind;
use App\Enums\GoalMeasure;
use App\Enums\GoalVisibility;
use App\Enums\Section;
use App\Models\Goal;
use App\Support\ChainStats;
use Illuminate\Database\Eloquent\Collection;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Attributes\Url;
use Livewire\Component;

new #[Layout('layouts::admin'), Title('Hedefler')] class extends Component {
    #[Url(as: 'yil')]
    public int $year = 0;

    /** @var list<int> */
    public array $copyIds = [];

    public function mount(): void
    {
        $this->year = $this->year ?: now()->year;
    }

    /**
     * @return Collection<int, Goal>
     */
    #[Computed]
    public function chains(): Collection
    {
        return Goal::query()->ofKind(GoalKind::Chain)->with(['chainDays', 'parent'])->orderBy('sort_order')->orderBy('id')->get();
    }

    /**
     * @return Collection<int, Goal>
     */
    #[Computed]
    public function yearlyGoals(): Collection
    {
        return Goal::query()->ofKind(GoalKind::Yearly)->where('year', $this->year)->with(['milestones', 'progressEntries', 'parent'])->orderBy('sort_order')->orderBy('id')->get();
    }

    /**
     * @return Collection<int, Goal>
     */
    #[Computed]
    public function longTermGoals(): Collection
    {
        return Goal::query()->ofKind(GoalKind::LongTerm)->withCount('children')->orderBy('sort_order')->orderBy('id')->get();
    }

    /**
     * @return Collection<int, Goal>
     */
    #[Computed]
    public function previousYearGoals(): Collection
    {
        return Goal::query()->ofKind(GoalKind::Yearly)->where('year', $this->year - 1)->orderBy('sort_order')->get();
    }

    /**
     * @return list<int>
     */
    #[Computed]
    public function years(): array
    {
        $years = Goal::query()->ofKind(GoalKind::Yearly)->distinct()->pluck('year')->map(fn ($year): int => (int) $year)->all();

        return collect([...$years, now()->year, now()->year + 1, $this->year])->unique()->sortDesc()->values()->all();
    }

    public function sort(int $id, int $position): void
    {
        Goal::query()->findOrFail($id)->moveTo($position);

        unset($this->chains, $this->yearlyGoals, $this->longTermGoals);
    }

    public function copyFromPreviousYear(CopyYearlyGoals $copy): void
    {
        $this->validate(['copyIds' => ['required', 'array', 'min:1']], attributes: ['copyIds' => 'hedefler']);

        $copies = $copy->handle(array_map('intval', $this->copyIds), $this->year);

        $this->reset('copyIds');
        unset($this->yearlyGoals);
        $this->dispatch('modal-close', name: 'copy-goals');
        $this->dispatch('toast', text: count($copies).' hedef '.$this->year.' yılına kopyalandı.');
    }
}; ?>

@php($visibilityColors = [GoalVisibility::Public->value => 'green', GoalVisibility::Censored->value => 'yellow', GoalVisibility::Hidden->value => 'zinc'])

<div class="space-y-10">
    <x-admin.page-header heading="Hedefler" description="Görünürlük: açık herkese, sansürlü yakınlara, gizli sadece sana." :dot="Section::Goals->adminDotClass()">
        <x-slot:actions>
            <x-admin.dropdown>
                <x-slot:trigger>
                    <x-admin.button variant="primary" icon="plus" icon-trailing="chevron-down">Yeni hedef</x-admin.button>
                </x-slot:trigger>
                @foreach (GoalKind::cases() as $kind)
                    <x-admin.dropdown.item :href="route('admin.goals.create', $kind->routeSegment())">{{ $kind->label() }}</x-admin.dropdown.item>
                @endforeach
            </x-admin.dropdown>
        </x-slot:actions>
    </x-admin.page-header>

    {{-- Chains --}}
    <section class="space-y-3">
        <x-admin.heading>Zincirler</x-admin.heading>
        @if ($this->chains->isEmpty())
            <x-admin.card padding="p-0"><x-admin.empty icon="flame" heading="Zincir yok">Her gün yapmak istediğin bir şey için bir zincir başlat.</x-admin.empty></x-admin.card>
        @else
            <ul wire:sort="sort" class="divide-y divide-zinc-100 overflow-hidden rounded-xl border border-zinc-200 bg-white dark:divide-zinc-800 dark:border-zinc-800 dark:bg-zinc-900">
                @foreach ($this->chains as $chain)
                    @php($states = array_column($chain->chainHistory(), 'state'))
                    <li wire:key="goal-{{ $chain->id }}" wire:sort:item="{{ $chain->id }}" class="flex items-center gap-3 px-4 py-3">
                        <button type="button" wire:sort:handle class="cursor-grab text-zinc-400" aria-label="Sırasını değiştir"><x-admin.icon name="grip-vertical" /></button>
                        <a href="{{ route('admin.goals.edit', $chain) }}" wire:navigate class="min-w-0 flex-1 font-bold hover:text-accent">
                            <span class="truncate">{{ $chain->title }}</span>
                            @if ($chain->parent)
                                <span class="block text-xs font-normal text-zinc-500">↑ {{ $chain->parent->title }}</span>
                            @endif
                        </a>
                        <span class="font-mono text-sm">🔥 {{ ChainStats::currentStreak($states) }}</span>
                        @if ($chain->ended_on)
                            <x-admin.badge>bitti</x-admin.badge>
                        @endif
                        <x-admin.badge :color="$visibilityColors[$chain->visibility->value]">{{ $chain->visibility->label() }}</x-admin.badge>
                    </li>
                @endforeach
            </ul>
        @endif
    </section>

    {{-- Yearly --}}
    <section class="space-y-3">
        <div class="flex flex-wrap items-center justify-between gap-3">
            <x-admin.heading>Yıllık hedefler</x-admin.heading>
            <div class="flex items-center gap-2">
                <x-admin.select wire:model.live="year" aria-label="Yıl" :options="collect($this->years)->mapWithKeys(fn ($year) => [$year => $year])->all()" />
                @if ($this->previousYearGoals->isNotEmpty())
                    <x-admin.modal.trigger name="copy-goals">
                        <x-admin.button icon="copy">{{ $year - 1 }}'den kopyala</x-admin.button>
                    </x-admin.modal.trigger>
                @endif
            </div>
        </div>

        @if ($this->yearlyGoals->isEmpty())
            <x-admin.card padding="p-0"><x-admin.empty icon="target" heading="{{ $year }} için hedef yok" /></x-admin.card>
        @else
            <ul wire:sort="sort" class="divide-y divide-zinc-100 overflow-hidden rounded-xl border border-zinc-200 bg-white dark:divide-zinc-800 dark:border-zinc-800 dark:bg-zinc-900">
                @foreach ($this->yearlyGoals as $goal)
                    <li wire:key="goal-{{ $goal->id }}" wire:sort:item="{{ $goal->id }}" class="flex items-center gap-3 px-4 py-3">
                        <button type="button" wire:sort:handle class="cursor-grab text-zinc-400" aria-label="Sırasını değiştir"><x-admin.icon name="grip-vertical" /></button>
                        <a href="{{ route('admin.goals.edit', $goal) }}" wire:navigate class="min-w-0 flex-1 font-bold hover:text-accent">
                            <span class="truncate">{{ $goal->title }}</span>
                            <span class="block text-xs font-normal text-zinc-500">{{ $goal->measure?->label() }} @if ($goal->parent) · ↑ {{ $goal->parent->title }} @endif</span>
                        </a>
                        <span class="font-mono text-sm">
                            @switch($goal->measure)
                                @case(GoalMeasure::Numeric) {{ $goal->current() }} / {{ $goal->target }} @break
                                @case(GoalMeasure::Milestones) {{ $goal->milestones->whereNotNull('done_at')->count() }} / {{ $goal->milestones->count() }} @break
                                @default {{ $goal->achieved_at ? '✓' : '—' }}
                            @endswitch
                        </span>
                        @if ($goal->isAchieved())
                            <x-admin.badge color="green">başarıldı</x-admin.badge>
                        @endif
                        <x-admin.badge :color="$visibilityColors[$goal->visibility->value]">{{ $goal->visibility->label() }}</x-admin.badge>
                    </li>
                @endforeach
            </ul>
        @endif
    </section>

    {{-- Long-term --}}
    <section class="space-y-3">
        <x-admin.heading>Uzun vade</x-admin.heading>
        @if ($this->longTermGoals->isEmpty())
            <x-admin.card padding="p-0"><x-admin.empty icon="target" heading="Uzun vadeli hedef yok" /></x-admin.card>
        @else
            <ul wire:sort="sort" class="divide-y divide-zinc-100 overflow-hidden rounded-xl border border-zinc-200 bg-white dark:divide-zinc-800 dark:border-zinc-800 dark:bg-zinc-900">
                @foreach ($this->longTermGoals as $goal)
                    <li wire:key="goal-{{ $goal->id }}" wire:sort:item="{{ $goal->id }}" class="flex items-center gap-3 px-4 py-3">
                        <button type="button" wire:sort:handle class="cursor-grab text-zinc-400" aria-label="Sırasını değiştir"><x-admin.icon name="grip-vertical" /></button>
                        <a href="{{ route('admin.goals.edit', $goal) }}" wire:navigate class="min-w-0 flex-1 truncate font-bold hover:text-accent">{{ $goal->title }}</a>
                        <span class="text-sm text-zinc-500">{{ $goal->children_count }} bağlı</span>
                        <x-admin.badge :color="$visibilityColors[$goal->visibility->value]">{{ $goal->visibility->label() }}</x-admin.badge>
                    </li>
                @endforeach
            </ul>
        @endif
    </section>

    <x-admin.modal name="copy-goals" :heading="($year - 1).' hedeflerini '.$year.' yılına kopyala'" description="İlerleme kopyalanmaz; kilometre taşları yapılmamış olarak gelir.">
        <form wire:submit="copyFromPreviousYear" class="space-y-4">
            <div class="max-h-72 space-y-2 overflow-y-auto">
                @foreach ($this->previousYearGoals as $goal)
                    <x-admin.checkbox wire:model="copyIds" :value="$goal->id" :label="$goal->title" :description="$goal->isAchieved() ? 'başarıldı' : 'olmadı'" wire:key="copy-{{ $goal->id }}" />
                @endforeach
            </div>
            <x-admin.error :message="$errors->first('copyIds')" />
            <div class="flex justify-end gap-2">
                <x-admin.modal.close><x-admin.button variant="ghost">Vazgeç</x-admin.button></x-admin.modal.close>
                <x-admin.button type="submit" variant="primary">Kopyala</x-admin.button>
            </div>
        </form>
    </x-admin.modal>
</div>
