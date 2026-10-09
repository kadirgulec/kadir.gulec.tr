<?php

use App\Models\PageView;
use Carbon\CarbonImmutable;
use Illuminate\Database\Eloquent\Builder;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Attributes\Url;
use Livewire\Component;

new #[Layout('layouts::admin'), Title('Ziyaretçiler')] class extends Component {
    /** @var list<int> */
    public const PERIODS = [7, 30, 90];

    #[Url(as: 'gun', except: 30)]
    public int $days = 30;

    /**
     * Page views and visitors per day, oldest first, days without views included.
     * A visitor is counted once per day: the hash changes every day.
     *
     * @return list<array{date: CarbonImmutable, views: int, visitors: int}>
     */
    #[Computed]
    public function daily(): array
    {
        $counts = $this->inPeriod()
            ->selectRaw('DATE(created_at) as day, COUNT(*) as views, COUNT(DISTINCT visitor_hash) as visitors')
            ->groupBy('day')
            ->get()
            ->keyBy(fn (PageView $row): string => (string) $row->getAttribute('day'));

        $days = [];

        for ($date = $this->start(); $date->lte(today()); $date = $date->addDay()) {
            $row = $counts->get($date->toDateString());
            $days[] = [
                'date' => $date,
                'views' => (int) $row?->getAttribute('views'),
                'visitors' => (int) $row?->getAttribute('visitors'),
            ];
        }

        return $days;
    }

    /**
     * @return array{visits: int, views: int, today: int}
     */
    #[Computed]
    public function totals(): array
    {
        $daily = collect($this->daily);

        return [
            'visits' => $daily->sum('visitors'),
            'views' => $daily->sum('views'),
            'today' => $daily->last()['visitors'],
        ];
    }

    /**
     * The ranked lists under the chart: what was read, where people came from
     * and what they used.
     *
     * @return array<string, array{column: string, rows: list<array{label: string, count: int}>}>
     */
    #[Computed]
    public function rankings(): array
    {
        $devices = ['desktop' => 'Masaüstü', 'mobile' => 'Telefon', 'tablet' => 'Tablet'];

        return [
            'Sayfalar' => ['column' => 'görüntüleme', 'rows' => $this->ranking('path', 'COUNT(*)', limit: 15)],
            'Nereden geldiler' => ['column' => 'ziyaret', 'rows' => array_map(
                fn (array $row): array => ['label' => $row['label'] === '' ? 'Doğrudan ya da bilinmiyor' : $row['label'], 'count' => $row['count']],
                $this->ranking("COALESCE(utm_source, referrer_host, '')", 'COUNT(DISTINCT visitor_hash)'),
            )],
            'Tarayıcı' => ['column' => 'ziyaret', 'rows' => $this->ranking('browser', 'COUNT(DISTINCT visitor_hash)')],
            'İşletim sistemi' => ['column' => 'ziyaret', 'rows' => $this->ranking('os', 'COUNT(DISTINCT visitor_hash)')],
            'Cihaz' => ['column' => 'ziyaret', 'rows' => array_map(
                fn (array $row): array => ['label' => $devices[$row['label']] ?? $row['label'], 'count' => $row['count']],
                $this->ranking('device', 'COUNT(DISTINCT visitor_hash)'),
            )],
        ];
    }

    public function updatedDays(): void
    {
        if (! in_array($this->days, self::PERIODS, true)) {
            $this->days = 30;
        }
    }

    /**
     * @return list<array{label: string, count: int}>
     */
    private function ranking(string $labelExpression, string $countExpression, int $limit = 10): array
    {
        return $this->inPeriod()
            ->selectRaw("{$labelExpression} as label, {$countExpression} as total")
            ->groupBy('label')
            ->orderByDesc('total')
            ->orderBy('label')
            ->limit($limit)
            ->get()
            ->map(fn (PageView $row): array => ['label' => (string) $row->getAttribute('label'), 'count' => (int) $row->getAttribute('total')])
            ->values()
            ->all();
    }

    /**
     * @return Builder<PageView>
     */
    private function inPeriod(): Builder
    {
        return PageView::query()->where('created_at', '>=', $this->start());
    }

    private function start(): CarbonImmutable
    {
        $days = in_array($this->days, self::PERIODS, true) ? $this->days : 30;

        return today()->subDays($days - 1);
    }
}; ?>

<div>
    <x-admin.page-header heading="Ziyaretçiler" description="Çerezsiz sayım: IP saklanmaz, bir ziyaretçi günde bir kez sayılır. Botlar, Do Not Track gönderenler ve adminler sayılmaz. {{ PageView::KEEP_MONTHS }} aydan eski kayıtlar kendiliğinden silinir.">
        <x-slot:actions>
            @foreach ($this::PERIODS as $period)
                <x-admin.button size="sm" :variant="$days === $period ? 'subtle' : 'ghost'" wire:click="$set('days', {{ $period }})">Son {{ $period }} gün</x-admin.button>
            @endforeach
        </x-slot:actions>
    </x-admin.page-header>

    <div class="mb-6 grid gap-4 sm:grid-cols-3">
        @foreach (['Ziyaret' => $this->totals['visits'], 'Sayfa görüntüleme' => $this->totals['views'], 'Bugünkü ziyaretçi' => $this->totals['today']] as $label => $value)
            <x-admin.card>
                <p class="text-sm text-zinc-500">{{ $label }}</p>
                <p class="mt-1 font-mono text-3xl font-bold text-zinc-900 tabular-nums dark:text-white">{{ number_format($value, 0, ',', '.') }}</p>
            </x-admin.card>
        @endforeach
    </div>

    @if ($this->totals['views'] === 0)
        <x-admin.card padding="p-0"><x-admin.empty icon="chart-column" heading="Bu dönemde kayıtlı ziyaret yok" /></x-admin.card>
    @else
        @php($peak = max(1, max(array_column($this->daily, 'visitors'))))

        <x-admin.card class="mb-6">
            <x-slot:heading>Günlük ziyaretçi</x-slot:heading>

            <div class="flex h-40 items-end gap-px" role="img" aria-label="Son {{ $days }} günün günlük ziyaretçi sayıları">
                @foreach ($this->daily as $day)
                    <div
                        wire:key="day-{{ $day['date']->toDateString() }}"
                        class="flex-1 rounded-t-sm bg-accent/80 hover:bg-accent"
                        style="height: {{ max(1, round($day['visitors'] / $peak * 100)) }}%"
                        title="{{ $day['date']->format('d.m.Y') }}: {{ $day['visitors'] }} ziyaretçi, {{ $day['views'] }} görüntüleme"
                    ></div>
                @endforeach
            </div>
            <div class="mt-2 flex justify-between font-mono text-xs text-zinc-500">
                <span>{{ $this->daily[0]['date']->format('d.m') }}</span>
                <span>bugün</span>
            </div>
        </x-admin.card>

        <div class="grid gap-6 lg:grid-cols-2">
            @foreach ($this->rankings as $heading => $ranking)
                @php($top = max(1, $ranking['rows'][0]['count'] ?? 1))

                <x-admin.card wire:key="ranking-{{ $loop->index }}" @class(['lg:col-span-2' => $loop->first])>
                    <x-slot:heading>{{ $heading }}</x-slot:heading>
                    <x-slot:actions><span class="text-xs text-zinc-500">{{ $ranking['column'] }}</span></x-slot:actions>

                    <ul class="space-y-1.5 text-sm">
                        @foreach ($ranking['rows'] as $row)
                            <li class="relative flex items-center justify-between gap-3 rounded px-2 py-1">
                                <span class="absolute inset-y-0 left-0 rounded bg-accent-soft dark:bg-accent/15" style="width: {{ round($row['count'] / $top * 100) }}%" aria-hidden="true"></span>
                                <span class="relative min-w-0 truncate font-mono text-zinc-800 dark:text-zinc-200">{{ $row['label'] }}</span>
                                <span class="relative font-mono text-zinc-600 tabular-nums dark:text-zinc-400">{{ $row['count'] }}</span>
                            </li>
                        @endforeach
                    </ul>
                </x-admin.card>
            @endforeach
        </div>
    @endif
</div>
