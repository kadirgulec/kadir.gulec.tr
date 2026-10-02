@use('App\Enums\Section')

@php
    // Calendar grid: one column per week (Monday first), one row per weekday.
    $statesByDate = collect($history)->mapWithKeys(fn (array $day): array => [$day['date']->toDateString() => $day['state']]);
    $firstDay = $history[0]['date'];
    $lastDay = end($history)['date'];
    $gridStart = $firstDay->startOfWeek();
    $weekCount = (int) ceil(($gridStart->diffInDays($lastDay) + 1) / 7);

    $stateLabels = ['done' => 'yapıldı', 'missed' => 'kaçırıldı', 'excused' => 'mazeretli'];
    $cellClasses = [
        'done' => 'bg-section',
        'excused' => 'bg-[repeating-linear-gradient(45deg,var(--color-section)_0_2px,transparent_2px_4px)] ring-1 ring-section/50',
        'missed' => 'bg-ink/10',
    ];
@endphp

<x-layouts::site :section="Section::Goals" :title="$chain['title'] ?? 'Sansürlü zincir'">
    <a href="{{ route('goals.index') }}" class="font-hand text-xl text-ink-soft hover:text-section-ink">← Hedefler</a>

    <p class="mt-8 font-mono text-xs tracking-widest text-ink-faint uppercase">zincir · {{ $lastDay->year }}</p>

    <h1 class="mt-3 font-display text-4xl leading-tight font-extrabold tracking-tight text-balance sm:text-5xl">
        @if ($chain['title'])
            {{ $chain['title'] }}
        @else
            <span aria-hidden="true">🔒</span>
            <x-site.censored :length="$chain['titleLength']" label="sansürlü zincir" />
        @endif
    </h1>

    <x-site.scribble class="mt-3 h-3.5 w-40 text-section" />

    @if ($parentGoal)
        <div class="mt-5">
            <x-site.parent-chip :parent="$parentGoal" />
        </div>
    @endif

    {{-- Numbers --}}
    <dl class="mt-10 grid grid-cols-2 gap-4 sm:grid-cols-5">
        @foreach ([
            ['label' => 'şu anki seri', 'value' => '🔥 '.$stats['streak']],
            ['label' => 'en uzun seri', 'value' => $stats['bestStreak']],
            ['label' => 'yapılan gün', 'value' => $stats['done']],
            ['label' => 'mazeretli', 'value' => $stats['excused']],
            ['label' => 'başarı', 'value' => '%'.$stats['successRate']],
        ] as $stat)
            <div class="rounded-sm bg-paper-deep p-4">
                <dt class="font-mono text-[11px] tracking-wider text-ink-faint uppercase">{{ $stat['label'] }}</dt>
                <dd class="mt-1 font-display text-3xl font-extrabold text-section-ink">{{ $stat['value'] }}</dd>
            </div>
        @endforeach
    </dl>

    {{-- The year --}}
    <section class="mt-12" aria-labelledby="yil">
        <h2 id="yil" class="font-display text-2xl font-semibold">{{ $lastDay->year }} boyunca</h2>

        {{-- data-scroll-end: on small screens the scroller starts at the newest weeks --}}
        <div class="mt-5 overflow-x-auto pb-3" data-scroll-end>
            <div class="inline-flex flex-col gap-1" role="img" aria-label="{{ $stats['done'] }} gün yapıldı, en uzun seri {{ $stats['bestStreak'] }} gün">
                <div class="flex gap-[3px] pl-6">
                    @for ($week = 0; $week < $weekCount; $week++)
                        @php
                            $weekStart = $gridStart->addWeeks($week);
                        @endphp
                        <span class="w-3 overflow-visible font-mono text-[10px] leading-none whitespace-nowrap text-ink-faint">
                            {{ $weekStart->day <= 7 ? $weekStart->locale('tr')->translatedFormat('M') : '' }}
                        </span>
                    @endfor
                </div>

                <div class="flex gap-[3px]">
                    <div class="mr-1 grid w-5 grid-rows-7 gap-[3px] font-mono text-[9px] leading-3 text-ink-faint" aria-hidden="true">
                        <span>Pzt</span><span></span><span>Çar</span><span></span><span>Cum</span><span></span><span></span>
                    </div>

                    @for ($week = 0; $week < $weekCount; $week++)
                        <div class="grid grid-rows-7 gap-[3px]">
                            @for ($weekday = 0; $weekday < 7; $weekday++)
                                @php
                                    $date = $gridStart->addWeeks($week)->addDays($weekday);
                                    $state = $statesByDate->get($date->toDateString());
                                @endphp
                                <span
                                    @class(['size-3 rounded-[3px]', $state ? $cellClasses[$state] : 'bg-transparent'])
                                    @if ($state) title="{{ $date->locale('tr')->translatedFormat('j F') }}: {{ $stateLabels[$state] }}" @endif
                                ></span>
                            @endfor
                        </div>
                    @endfor
                </div>
            </div>
        </div>

        <p class="mt-4 flex flex-wrap items-center gap-x-5 gap-y-2 font-hand text-lg text-ink-faint">
            <span class="flex items-center gap-2"><span class="size-3 rounded-[3px] bg-section"></span> yapıldı</span>
            <span class="flex items-center gap-2"><span class="size-3 rounded-[3px] {{ $cellClasses['excused'] }}"></span> mazeretli (zinciri kırmaz)</span>
            <span class="flex items-center gap-2"><span class="size-3 rounded-[3px] bg-ink/10"></span> kaçırıldı</span>
        </p>
    </section>

    {{-- The last weeks as links --}}
    <section class="mt-12" aria-labelledby="son-gunler">
        <h2 id="son-gunler" class="font-display text-2xl font-semibold">Son üç hafta</h2>
        <x-site.chain :days="$chain['days']" class="mt-4 origin-left scale-125 sm:scale-150" />
    </section>
</x-layouts::site>
