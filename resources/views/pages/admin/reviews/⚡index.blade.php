<?php

use App\Enums\Section;
use App\Models\MonthlyReview;
use App\Support\TurkishDate;
use Carbon\CarbonImmutable;
use Illuminate\Database\Eloquent\Collection;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;

new #[Layout('layouts::admin'), Title('Aylık değerlendirme')] class extends Component {
    /** The month to make by hand (yyyy-mm), last month by default. */
    public string $month = '';

    public function mount(): void
    {
        $this->month = CarbonImmutable::today()->subMonthNoOverflow()->format('Y-m');
    }

    /**
     * @return Collection<int, MonthlyReview>
     */
    #[Computed]
    public function reviews(): Collection
    {
        return MonthlyReview::query()->withCount('items')->orderByDesc('month')->get();
    }

    /**
     * Makes a month's review now, numbers frozen as reviews:create would (no e-mail).
     */
    public function create(): void
    {
        $this->validate(['month' => ['required', 'date_format:Y-m']], attributes: ['month' => 'ay']);

        $month = CarbonImmutable::createFromFormat('!Y-m', $this->month);

        if (MonthlyReview::query()->whereDate('month', $month->toDateString())->exists()) {
            $this->addError('month', TurkishDate::monthYear($month).' zaten var.');

            return;
        }

        $review = MonthlyReview::makeFor($month);
        $review->save();

        $this->redirectRoute('admin.reviews.edit', $review, navigate: true);
    }
}; ?>

<div>
    <x-admin.page-header heading="Aylık değerlendirme" description="Her ayın 1'inde geçen ayın taslağı rakamlarıyla kendiliğinden oluşur." :dot="Section::Goals->adminDotClass()">
        <x-slot:actions>
            @if (Route::has('goals.reviews.index'))
                <x-admin.button icon="external-link" :href="route('goals.reviews.index')" target="_blank">Sitede gör</x-admin.button>
            @endif
        </x-slot:actions>
    </x-admin.page-header>

    <x-admin.card class="mb-6">
        <form wire:submit="create" class="flex flex-wrap items-end gap-3">
            <x-admin.input wire:model="month" type="month" label="Bir ayı şimdi oluştur" description="Geriye dönük ya da denemek için. E-posta gitmez." class="w-56" />
            <x-admin.button type="submit" icon="plus">Oluştur</x-admin.button>
        </form>
    </x-admin.card>

    @if ($this->reviews->isEmpty())
        <x-admin.card padding="p-0">
            <x-admin.empty icon="calendar-check" heading="Henüz değerlendirme yok">
                İlki ayın 1'inde taslak olarak gelecek. İstersen yukarıdan şimdi oluşturabilirsin.
            </x-admin.empty>
        </x-admin.card>
    @else
        <x-admin.table>
            <x-admin.table.columns>
                <x-admin.table.column>Ay</x-admin.table.column>
                <x-admin.table.column>Durum</x-admin.table.column>
                <x-admin.table.column>Puan</x-admin.table.column>
                <x-admin.table.column>Madde</x-admin.table.column>
            </x-admin.table.columns>
            <x-admin.table.rows>
                @foreach ($this->reviews as $review)
                    <x-admin.table.row wire:key="review-{{ $review->id }}">
                        <x-admin.table.cell variant="strong">
                            <a href="{{ route('admin.reviews.edit', $review) }}" wire:navigate class="font-semibold hover:text-accent">{{ TurkishDate::monthYear($review->month) }}</a>
                            @if ($review->summary)
                                <span class="block max-w-md truncate text-xs font-normal text-zinc-500">{{ $review->summary }}</span>
                            @endif
                        </x-admin.table.cell>
                        <x-admin.table.cell><x-admin.badge :color="$review->publicationState()->color()">{{ $review->publicationState()->label() }}</x-admin.badge></x-admin.table.cell>
                        <x-admin.table.cell class="font-mono text-xs">{{ $review->score !== null ? $review->score.'/10' : '—' }}</x-admin.table.cell>
                        <x-admin.table.cell class="font-mono text-xs">{{ $review->items_count }}</x-admin.table.cell>
                    </x-admin.table.row>
                @endforeach
            </x-admin.table.rows>
        </x-admin.table>
    @endif
</div>
