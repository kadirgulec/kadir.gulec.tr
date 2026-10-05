@use('App\Enums\Section')
@use('App\Enums\GoalPace')
@use('App\Support\TurkishDate')

@php
    $pinColors = ['var(--color-pen-red)', 'var(--color-goals)', 'var(--color-projects)', 'var(--color-about)', 'var(--color-home)'];
    $boardTilts = [-2, 1.5, -1, 2.5, -1.5];
    $paceColors = [
        GoalPace::Ahead->value => 'text-goals-ink',
        GoalPace::OnTrack->value => 'text-ink-soft',
        GoalPace::Behind->value => 'text-projects-ink',
    ];
    $cardShadow = 'shadow-[0_10px_22px_-14px_rgb(60_40_20/0.5)] dark:shadow-[0_10px_22px_-10px_rgb(0_0_0/0.85)]';
@endphp

<x-layouts::site :section="Section::Goals" title="Hedefler" description="Günlük zincirler, bu yılın hedefleri ve uzun vadeli hayaller; tutanlar da tutmayanlar da.">
    <p class="font-mono text-xs tracking-widest text-ink-faint uppercase">{{ $today->locale('tr')->translatedFormat('j F Y') }}</p>

    <h1 class="relative mt-3 inline-block font-display text-5xl font-extrabold tracking-tight sm:text-6xl">
        Hedefler
        <x-site.scribble class="absolute -bottom-3 left-0 h-3.5 w-full text-section" />
    </h1>

    <p class="mt-8 font-hand text-2xl text-ink-soft">küçük adımlar, büyük hedefler. yukarıdan aşağıya uzaklaşıyoruz ↓</p>

    <div class="mt-6 flex max-w-md items-center gap-4">
        <span class="shrink-0 font-mono text-xs text-ink-soft">{{ $today->year }}</span>
        <x-site.pencil-progress :value="round($yearShare * 100)" :max="100" label="Yılın ilerlemesi" class="flex-1" />
        <span class="shrink-0 font-mono text-xs text-ink-soft">%{{ round($yearShare * 100) }}</span>
    </div>

    {{-- Floor 1: chains (daily, weekly, monthly habits) --}}
    <section class="mt-16" aria-labelledby="zincirler">
        <p class="font-hand text-xl text-section-ink">1 · alışkanlıklar</p>
        <h2 id="zincirler" class="font-display text-3xl font-semibold">Zincirler</h2>

        @unless ($chains)
            <p class="mt-6 font-hand text-2xl text-section-ink">Şu an süren bir zincir yok.</p>
        @endunless

        <div class="mt-6 grid gap-6 sm:grid-cols-2">
            @foreach ($chains as $chain)
                <article id="hedef-{{ $chain['slug'] }}" class="relative min-w-0 scroll-mt-8 rounded-sm bg-paper-deep p-5 {{ $cardShadow }}">
                    <div class="flex items-start justify-between gap-4">
                        <h3 class="min-w-0 font-semibold">
                            <a href="{{ route('goals.chain', $chain['slug']) }}" class="hover:text-section-ink">
                                @if ($chain['title'])
                                    {{ $chain['title'] }}
                                @else
                                    <span aria-hidden="true">🔒</span>
                                    <x-site.censored :length="$chain['titleLength']" label="sansürlü zincir" />
                                @endif
                            </a>
                            <span class="block font-mono text-[11px] font-normal text-ink-faint">{{ $chain['cadence'] }}</span>
                        </h3>

                        <p class="shrink-0 text-right leading-none">
                            <span class="font-display text-3xl font-extrabold text-section-ink">🔥 {{ $chain['streak'] }}</span>
                            <span class="block font-mono text-[11px] text-ink-faint">{{ $chain['period']->unit() }}</span>
                        </p>
                    </div>

                    <x-site.chain :days="$chain['days']" :unit="$chain['period']->unit()" class="mt-3" />

                    <div class="mt-4 flex flex-wrap items-center justify-between gap-2">
                        <a href="{{ route('goals.chain', $chain['slug']) }}" class="font-mono text-[11px] text-ink-faint hover:text-section-ink">en uzun seri: {{ $chain['bestStreak'] }} {{ $chain['period']->unit() }} · yıllık görünüm →</a>
                        @if ($chain['parentGoal'])
                            <x-site.parent-chip :parent="$chain['parentGoal']" />
                        @endif
                    </div>
                </article>
            @endforeach
        </div>

        <p class="mt-5 flex flex-wrap items-center gap-x-5 gap-y-1 font-hand text-lg text-ink-faint">
            <span class="flex items-center gap-2"><span class="inline-block h-3 w-1.5 rotate-[28deg] bg-ink-faint/55" aria-hidden="true"></span> bantlı halka = mazeretli (zinciri kırmaz)</span>
            <span>kopuk halka = kaçırıldı</span>
            <span>haftalık ve aylık zincirlerde her halka bir dönem</span>
        </p>
    </section>

    <p class="my-16 text-center font-hand text-2xl text-ink-faint" aria-hidden="true">↓ biraz uzaklaşalım ↓</p>

    {{-- Floor 2: this year --}}
    <section aria-labelledby="bu-yil">
        <p class="font-hand text-xl text-section-ink">2 · bu yıl</p>
        <h2 id="bu-yil" class="font-display text-3xl font-semibold">{{ $today->year }} hedefleri</h2>

        @unless ($yearlyGoals)
            <p class="mt-6 font-hand text-2xl text-section-ink">Bu yılın hedefleri henüz deftere yazılmadı.</p>
        @endunless

        <div class="mt-6 grid items-start gap-6 md:grid-cols-2">
            @foreach ($yearlyGoals as $goal)
                <article id="hedef-{{ $goal['slug'] }}" class="relative scroll-mt-8 overflow-hidden rounded-sm bg-paper-deep p-5 {{ $cardShadow }}">
                    <div class="flex items-start justify-between gap-4">
                        <h3 class="min-w-0 font-display text-xl leading-tight font-semibold text-balance">
                            @if ($goal['title'])
                                {{ $goal['title'] }}
                            @else
                                <span aria-hidden="true">🔒</span>
                                <x-site.censored :length="$goal['titleLength']" label="sansürlü hedef" />
                            @endif
                        </h3>

                        @if ($goal['type'] === 'milestones')
                            <span class="shrink-0 font-mono text-xs text-ink-soft">{{ collect($goal['milestones'])->where('done', true)->count() }}/{{ count($goal['milestones']) }}</span>
                        @endif
                    </div>

                    @switch($goal['type'])
                        @case('numeric')
                            <p class="mt-4 font-display text-4xl leading-none font-extrabold">
                                {{ $goal['current'] }}
                                <span class="font-sans text-base font-semibold text-ink-soft">/ {{ $goal['target'] }} {{ $goal['unit'] }}</span>
                            </p>

                            <x-site.pencil-progress
                                :value="$goal['current']"
                                :max="$goal['target']"
                                :marker="$yearShare"
                                :label="($goal['title'] ?? 'Sansürlü hedef').' ilerlemesi'"
                                class="mt-4 mb-6"
                            />

                            @if ($goal['pace'])
                                <p class="font-hand text-xl {{ $paceColors[$goal['pace']->value] }}">{{ $goal['pace']->label() }}</p>
                            @endif

                            @if ($goal['progressNotes'])
                                <details class="mt-3 text-sm">
                                    <summary class="cursor-pointer font-hand text-lg text-section-ink">notlar ({{ count($goal['progressNotes']) }})</summary>
                                    <ul class="mt-2 flex flex-col gap-1">
                                        @foreach ($goal['progressNotes'] as $note)
                                            <li class="flex gap-3"><span class="w-12 shrink-0 font-mono text-xs text-ink-faint">{{ $note['date']->format('d.m') }}</span><span>{{ $note['note'] }}</span></li>
                                        @endforeach
                                    </ul>
                                </details>
                            @endif
                            @break

                        @case('milestones')
                            <ul class="mt-4 flex flex-col gap-2">
                                @foreach ($goal['milestones'] as $milestone)
                                    <li class="flex items-center gap-3">
                                        <x-site.checkbox :checked="$milestone['done']" class="size-5 text-ink-soft" />
                                        <span @class(['line-through decoration-ink-faint decoration-2 text-ink-soft' => $milestone['done']])>
                                            @if ($milestone['title'])
                                                {{ $milestone['title'] }}
                                            @else
                                                <x-site.censored :length="10" />
                                            @endif
                                        </span>
                                    </li>
                                @endforeach
                            </ul>

                            @if ($goal['linkUrl'])
                                <a href="{{ $goal['linkUrl'] }}" class="mt-4 inline-block text-sm font-semibold text-section-ink underline decoration-section decoration-2 underline-offset-4">projeye git →</a>
                            @endif
                            @break

                        @case('binary')
                            <div class="mt-4 flex items-center gap-4">
                                <x-site.checkbox :checked="(bool) $goal['achievedAt']" class="size-12 text-ink-soft" />
                                <p class="font-hand text-2xl text-ink-soft">
                                    {{ $goal['achievedAt'] ? TurkishDate::onDayMonth($goal['achievedAt']).' oldu!' : 'henüz değil' }}
                                </p>
                            </div>

                            @if ($goal['achievedAt'])
                                <span class="stamp press pointer-events-none absolute top-1/2 right-5 -rotate-12 rounded-[4px] border-[3px] border-goals-ink px-2.5 py-1 font-mono text-sm font-bold tracking-[0.2em] text-goals-ink">BAŞARILDI</span>
                            @endif
                            @break
                    @endswitch

                    <div class="mt-4 flex flex-wrap items-center gap-3 text-sm">
                        @if ($goal['parentGoal'])
                            <x-site.parent-chip :parent="$goal['parentGoal']" />
                        @endif
                        <livewire:site.follow-button type="goal" :id="$goal['id']" :key="'follow-goal-'.$goal['id']" />
                    </div>
                </article>
            @endforeach
        </div>

        {{-- Past years: kept honestly --}}
        @if ($pastYearGoals)
        <div class="mt-12">
            <h3 class="font-display text-xl font-semibold">Geçmiş yıllar</h3>
            <p class="font-hand text-lg text-ink-faint">tutmayanlar silinmez, sadece üstleri karalanır</p>

            @foreach ($pastYearGoals as $year => $goals)
                <div class="mt-4 grid gap-x-6 sm:grid-cols-[4rem_1fr]">
                    <p class="font-mono text-sm font-semibold text-ink-soft">{{ $year }}</p>

                    <ul class="flex flex-col gap-2">
                        @foreach ($goals as $pastGoal)
                            <li class="flex items-center gap-3">
                                <x-site.checkbox :checked="$pastGoal['achieved']" class="size-5 text-ink-faint" />
                                @if ($pastGoal['title'] === null)
                                    <x-site.censored :length="$pastGoal['titleLength']" label="sansürlü hedef" />
                                    @unless ($pastGoal['achieved'])
                                        <span class="font-hand text-lg text-ink-faint">olmadı</span>
                                    @endunless
                                @elseif ($pastGoal['achieved'])
                                    <span>{{ $pastGoal['title'] }}</span>
                                @else
                                    <span class="scribbled-out text-ink-faint">{{ $pastGoal['title'] }}</span>
                                    <span class="font-hand text-lg text-ink-faint">olmadı</span>
                                @endif
                            </li>
                        @endforeach
                    </ul>
                </div>
            @endforeach
        </div>
        @endif
    </section>

    <p class="my-16 text-center font-hand text-2xl text-ink-faint" aria-hidden="true">↓ daha da uzaklaşalım ↓</p>

    {{-- Floor 3: the long-term board --}}
    <section aria-labelledby="uzun-vade">
        <p class="font-hand text-xl text-section-ink">3 · yıllar boyu</p>
        <h2 id="uzun-vade" class="font-display text-3xl font-semibold">Uzun vade</h2>

        <div class="cork mt-6 rounded-md p-6 pt-8 sm:p-10">
            @unless ($longTermGoals)
                <p class="font-hand text-2xl text-paper">Panoda henüz bir kart yok.</p>
            @endunless

            <div class="grid gap-x-8 gap-y-10 sm:grid-cols-2 lg:grid-cols-3">
                @foreach ($longTermGoals as $goal)
                    <article
                        id="hedef-{{ $goal['slug'] }}"
                        style="--tilt: {{ $boardTilts[$loop->index % count($boardTilts)] }}deg"
                        class="relative scroll-mt-8 rotate-(--tilt) bg-paper p-5 pt-7 shadow-[2px_8px_16px_-6px_rgb(0_0_0/0.45)] transition duration-200 hover:rotate-0 motion-reduce:transition-none"
                    >
                        <x-site.push-pin :color="$pinColors[$loop->index % count($pinColors)]" class="absolute -top-3 left-1/2 -translate-x-1/2" />

                        <h3 class="font-display text-xl leading-tight font-semibold text-balance">
                            @if ($goal['title'])
                                <a href="{{ route('goals.show', $goal['slug']) }}" class="hover:text-section-ink">{{ $goal['title'] }}</a>
                            @else
                                <span aria-hidden="true">🔒</span>
                                <x-site.censored :length="$goal['titleLength']" label="sansürlü uzun vadeli hedef" />
                            @endif
                        </h3>

                        @if ($goal['imageUrl'])
                            <img src="{{ $goal['imageUrl'] }}" alt="" class="mt-3 aspect-[4/3] w-full object-cover" loading="lazy" />
                        @endif

                        <div class="mt-3 text-sm leading-relaxed text-ink-soft [&_p+p]:mt-2">
                            @if ($goal['why'])
                                {{ $goal['why'] }}
                            @elseif ($goal['whyLength'])
                                <x-site.censored :length="$goal['whyLength']" />
                            @endif
                        </div>

                        <p class="mt-4 flex flex-wrap justify-between gap-2 border-t border-dashed border-rule pt-2 font-mono text-[11px] text-ink-faint">
                            <span>@if ($goal['since']) başlangıç: {{ $goal['since'] }} @endif</span>
                            @if ($goal['childCount'])
                                <span>{{ $goal['childCount'] }} bağlı hedef</span>
                            @endif
                        </p>

                        @if ($goal['title'])
                            <a href="{{ route('goals.show', $goal['slug']) }}" class="mt-3 inline-block font-hand text-lg text-section-ink underline decoration-section decoration-wavy underline-offset-4">hikâyesi →</a>
                        @endif
                    </article>
                @endforeach
            </div>
        </div>
    </section>
</x-layouts::site>
