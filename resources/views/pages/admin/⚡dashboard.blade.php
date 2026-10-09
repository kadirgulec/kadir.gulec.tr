<?php

use App\Actions\Goals\MarkChainDay;
use App\Actions\Notes\SaveNote;
use App\Enums\ChainDayState;
use App\Enums\GoalKind;
use App\Enums\GoalMeasure;
use App\Enums\Permission;
use App\Livewire\Forms\NoteForm;
use App\Models\Goal;
use App\Models\MonthlyReview;
use App\Models\Note;
use App\Models\Post;
use App\Models\Project;
use App\Models\Tag;
use App\Models\Watchable;
use App\Enums\ChainPeriod;
use App\Support\ChainReminders;
use App\Support\ChainStats;
use App\Support\TurkishDate;
use Carbon\CarbonImmutable;
use Illuminate\Database\Eloquent\Collection;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;

new #[Layout('layouts::admin'), Title('Pano')] class extends Component {
    /** @var array<int, string> Notes typed next to the +1 buttons, by goal id. */
    public array $progressNotes = [];

    /** The "quick note" card: a note published the moment it is stuck on. */
    public NoteForm $quickNote;

    /**
     * @return Collection<int, Goal>
     */
    #[Computed]
    public function chains(): Collection
    {
        return Goal::query()->activeChains()->with('chainDays')->orderBy('sort_order')->orderBy('id')->get();
    }

    /**
     * @return Collection<int, Goal>
     */
    #[Computed]
    public function numericGoals(): Collection
    {
        return Goal::query()->ofKind(GoalKind::Yearly)->where('year', now()->year)->where('measure', GoalMeasure::Numeric)
            ->with('progressEntries')->orderBy('sort_order')->get()
            ->reject(fn (Goal $goal): bool => $goal->isAchieved());
    }

    /**
     * The newest review that is not out yet, to remind Kadir to write it.
     */
    #[Computed]
    public function draftReview(): ?MonthlyReview
    {
        return MonthlyReview::query()->whereNull('published_at')->orderByDesc('month')->first();
    }

    /**
     * @return array{posts: int, notes: int, projects: int, watched: int}
     */
    #[Computed]
    public function drafts(): array
    {
        return [
            'posts' => Post::query()->whereNull('published_at')->count(),
            'notes' => Note::query()->whereNull('published_at')->count(),
            'projects' => Project::query()->whereNull('published_at')->count(),
            'watched' => Watchable::query()->whereNull('published_at')->count(),
        ];
    }

    /**
     * @return list<string>
     */
    #[Computed]
    public function tagOptions(): array
    {
        return Tag::query()->orderBy('name')->pluck('name')->all();
    }

    public function addQuickNote(SaveNote $saveNote): void
    {
        $this->authorize(Permission::ManageNotes->value);

        $this->quickNote->startNew();
        $note = $this->quickNote->store($saveNote);
        $this->quickNote->reset();

        unset($this->tagOptions);
        $this->dispatch('toast', text: 'Not #'.$note->id.' panoya yapıştı.', variant: 'success');
    }

    public function mark(int $chainId, string $day, string $state, MarkChainDay $markChainDay): void
    {
        $this->authorize(Permission::ManageGoals->value);

        $date = $day === 'yesterday' ? CarbonImmutable::yesterday() : CarbonImmutable::today();
        $chain = Goal::query()->activeChains()->findOrFail($chainId);

        $markChainDay->toggle($chain, $date, ChainDayState::from($state));
        unset($this->chains);
    }

    public function addOne(int $goalId): void
    {
        $this->authorize(Permission::ManageGoals->value);
        $this->validate(['progressNotes.'.$goalId => ['nullable', 'string', 'max:160']], attributes: ['progressNotes.'.$goalId => 'not']);

        $goal = Goal::query()->ofKind(GoalKind::Yearly)->where('measure', GoalMeasure::Numeric)->findOrFail($goalId);
        $goal->progressEntries()->create(['date' => today(), 'amount' => 1, 'note' => ($this->progressNotes[$goalId] ?? '') ?: null]);

        unset($this->progressNotes[$goalId], $this->numericGoals);
        $this->dispatch('toast', text: $goal->title.': '.$goal->current().' / '.$goal->target);
    }
}; ?>

@php
    $today = CarbonImmutable::today();
    $yesterday = $today->subDay();
@endphp

<div>
    <x-admin.page-header heading="Pano" :description="'Merhaba '.auth()->user()->name.', defterde bugün ne var?'" />

    @can(Permission::ManageGoals->value)
        @if ($this->draftReview)
            <a href="{{ route('admin.reviews.edit', $this->draftReview) }}" wire:navigate class="mb-6 flex items-center gap-3 rounded-xl border border-section-goals/40 bg-section-goals/10 px-5 py-3.5 text-sm hover:bg-section-goals/20">
                <x-admin.icon name="calendar-check" class="text-[#4f7000] dark:text-section-goals" />
                <span class="flex-1"><strong>{{ TurkishDate::monthYear($this->draftReview->month) }} değerlendirmesi</strong> taslakta bekliyor. Rakamlar hazır, gerisi sende.</span>
                <x-admin.icon name="arrow-right" class="text-zinc-400" />
            </a>
        @endif
    @endcan

    <div class="grid gap-6 lg:grid-cols-[1.4fr_1fr]">
        @can(Permission::ManageGoals->value)
            <x-admin.card>
                <x-slot:heading>Bugün · {{ $today->locale('tr')->translatedFormat('j F l') }}</x-slot:heading>

                @if ($this->chains->isEmpty() && $this->numericGoals->isEmpty())
                    <x-admin.empty icon="flame" heading="Bugün işaretlenecek bir şey yok">
                        <x-slot:actions>
                            <x-admin.button size="sm" :href="route('admin.goals.create', 'zincir')" icon="plus">Zincir başlat</x-admin.button>
                        </x-slot:actions>
                    </x-admin.empty>
                @endif

                <ul class="divide-y divide-zinc-100 dark:divide-zinc-800">
                    @foreach ($this->chains as $chain)
                        @php
                            $marks = $chain->chainDays->keyBy(fn ($day) => $day->date->toDateString());
                            $todayState = $marks->get($today->toDateString())?->state;
                            $yesterdayState = $marks->get($yesterday->toDateString())?->state;
                            $canMarkYesterday = ! $chain->started_on || $chain->started_on->lessThanOrEqualTo($yesterday);
                            $streak = ChainStats::currentStreak(array_column($chain->chainLinks(), 'state'));
                            $period = $chain->chain_period === ChainPeriod::Day ? null : $chain->chainPeriodAt($today);
                        @endphp
                        <li wire:key="chain-{{ $chain->id }}" class="flex flex-wrap items-center gap-x-3 gap-y-2 py-3 first:pt-0">
                            <div class="min-w-0 flex-1 basis-40">
                                <a href="{{ route('admin.goals.edit', $chain) }}" wire:navigate class="block truncate font-bold hover:text-accent">{{ $chain->title }}</a>
                                <span class="font-mono text-xs text-zinc-500">🔥 {{ $streak }} {{ $chain->chain_period->unit() }}</span>
                                @if ($period)
                                    @if ($period['needed'] === 0)
                                        <x-admin.badge color="green">{{ ChainReminders::progress($chain, $period) }} ✓</x-admin.badge>
                                    @elseif (ChainReminders::isDue($period))
                                        <x-admin.badge color="red">{{ ChainReminders::progress($chain, $period) }} · {{ $period['daysLeft'] }} günde {{ $period['needed'] }} kez</x-admin.badge>
                                    @else
                                        <x-admin.badge>{{ ChainReminders::progress($chain, $period) }} · {{ $period['daysLeft'] }} gün kaldı</x-admin.badge>
                                    @endif
                                @endif
                            </div>

                            <div class="flex items-center gap-1.5">
                                <x-admin.button size="sm" :variant="$todayState === ChainDayState::Done ? 'primary' : 'outline'" icon="check" wire:click="mark({{ $chain->id }}, 'today', 'done')" :aria-pressed="$todayState === ChainDayState::Done ? 'true' : 'false'">Tamam</x-admin.button>
                                <x-admin.button size="sm" :variant="$todayState === ChainDayState::Excused ? 'subtle' : 'ghost'" icon="bandage" wire:click="mark({{ $chain->id }}, 'today', 'excused')" :aria-pressed="$todayState === ChainDayState::Excused ? 'true' : 'false'" title="Mazeretli gün: zinciri kırmaz">Mazeret</x-admin.button>
                            </div>

                            @if ($canMarkYesterday)
                                <div class="flex items-center gap-1 text-xs text-zinc-500">
                                    dün:
                                    @if ($yesterdayState === ChainDayState::Done)
                                        <button type="button" wire:click="mark({{ $chain->id }}, 'yesterday', 'done')" class="cursor-pointer font-bold text-green-700 hover:underline dark:text-green-400" title="Geri al">✓</button>
                                    @elseif ($yesterdayState === ChainDayState::Excused)
                                        <button type="button" wire:click="mark({{ $chain->id }}, 'yesterday', 'excused')" class="cursor-pointer font-bold text-amber-600 hover:underline" title="Geri al">mazeret</button>
                                    @else
                                        <button type="button" wire:click="mark({{ $chain->id }}, 'yesterday', 'done')" class="cursor-pointer font-semibold text-accent underline underline-offset-2">işaretle</button>
                                    @endif
                                </div>
                            @endif
                        </li>
                    @endforeach

                    @foreach ($this->numericGoals as $goal)
                        <li wire:key="numeric-{{ $goal->id }}" class="flex flex-wrap items-center gap-x-3 gap-y-2 py-3">
                            <div class="min-w-0 flex-1 basis-40">
                                <a href="{{ route('admin.goals.edit', $goal) }}" wire:navigate class="block truncate font-bold hover:text-accent">{{ $goal->title }}</a>
                                <span class="font-mono text-xs text-zinc-500">{{ $goal->current() }} / {{ $goal->target }} {{ $goal->unit }}</span>
                            </div>
                            <x-admin.input wire:model="progressNotes.{{ $goal->id }}" placeholder="not (opsiyonel)" aria-label="{{ $goal->title }}: not" class="w-44" />
                            <x-admin.button size="sm" icon="plus" wire:click="addOne({{ $goal->id }})">1</x-admin.button>
                        </li>
                    @endforeach
                </ul>
            </x-admin.card>
        @endcan

        <div class="space-y-6">
        @can(Permission::ManageNotes->value)
            <x-admin.card>
                <x-slot:heading>Hızlı not</x-slot:heading>
                <x-slot:actions>
                    <x-admin.link :href="route('admin.notes.index')" class="text-xs">Öğrendiklerim</x-admin.link>
                </x-slot:actions>

                <form wire:submit="addQuickNote" class="space-y-3">
                    <div class="space-y-1.5">
                        <x-admin.textarea wire:model="quickNote.body" rows="3" mono aria-label="Bugün ne öğrendin?" placeholder="Bugün ne öğrendin?" />
                        <x-admin.char-counter field="quickNote.body" :soft="NoteForm::SOFT_LIMIT" :hard="NoteForm::HARD_HINT" />
                    </div>
                    <div class="flex items-start gap-2">
                        <x-admin.input wire:model="quickNote.tagName" list="quick-note-tags" autocomplete="off" placeholder="etiket" aria-label="Etiket" class="flex-1" />
                        <datalist id="quick-note-tags">
                            @foreach ($this->tagOptions as $tagName)
                                <option value="{{ $tagName }}"></option>
                            @endforeach
                        </datalist>
                        <x-admin.button type="submit" variant="primary" icon="sticky-note" class="mt-px">Yapıştır</x-admin.button>
                    </div>
                </form>
            </x-admin.card>
        @endcan

        <x-admin.card>
            <x-slot:heading>Taslaklar</x-slot:heading>
            <ul class="space-y-2 text-sm">
                @foreach ([['admin.posts.index', 'Yazılar', 'posts', ['durum' => 'draft']], ['admin.notes.index', 'Öğrendiklerim', 'notes', ['durum' => 'draft']], ['admin.projects.index', 'Projeler', 'projects', []], ['admin.watched.index', 'İzlediklerim', 'watched', []]] as [$routeName, $label, $key, $query])
                    @if (Route::has($routeName))
                        <li class="flex items-center justify-between">
                            <x-admin.link :href="route($routeName, $query)">{{ $label }}</x-admin.link>
                            <x-admin.badge>{{ $this->drafts[$key] }}</x-admin.badge>
                        </li>
                    @endif
                @endforeach
            </ul>
        </x-admin.card>
        </div>
    </div>
</div>
