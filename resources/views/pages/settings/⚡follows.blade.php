<?php

use App\Enums\GoalKind;
use App\Enums\GoalVisibility;
use App\Models\Follow;
use App\Models\Goal;
use App\Models\Project;
use App\Models\Watchable;
use App\Support\Notifications\Notifier;
use Illuminate\Database\Eloquent\Collection;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;

new #[Layout('layouts::account'), Title('Takip ettiklerim')] class extends Component {
    /**
     * @return Collection<int, Follow>
     */
    #[Computed]
    public function follows(): Collection
    {
        return auth()->user()->follows()->with('followable')->latest()->get()
            ->filter(fn (Follow $follow): bool => $follow->followable !== null);
    }

    public function unfollow(int $id): void
    {
        auth()->user()->follows()->whereKey($id)->delete();
        unset($this->follows);
    }

    /**
     * @return array{name: string, kind: string, url: ?string, muted: bool}
     */
    public function describe(Follow $follow): array
    {
        $followable = $follow->followable;
        $notifier = app(Notifier::class);

        return match (true) {
            $followable instanceof Watchable => ['name' => $followable->title, 'kind' => $followable->type->label(), 'url' => route('watched.show', ['type' => $followable->type->routeSegment(), 'slug' => $followable->slug]), 'muted' => false],
            $followable instanceof Project => ['name' => $followable->name, 'kind' => 'Proje', 'url' => route('projects.show', $followable->slug), 'muted' => false],
            $followable instanceof Goal => [
                'name' => $notifier->goalTitle($followable, auth()->user()),
                'kind' => $followable->kind->label(),
                'url' => match ($followable->kind) {
                    GoalKind::Chain => route('goals.chain', $notifier->goalSlug($followable, auth()->user())),
                    GoalKind::LongTerm => route('goals.show', $notifier->goalSlug($followable, auth()->user())),
                    GoalKind::Yearly => route('goals.index').'#hedef-'.$notifier->goalSlug($followable, auth()->user()),
                },
                'muted' => $followable->visibility === GoalVisibility::Hidden,
            ],
            default => ['name' => 'Kayıt', 'kind' => '', 'url' => null, 'muted' => false],
        };
    }
}; ?>

<div class="space-y-6">
    <header class="space-y-1">
        <h1 class="font-display text-4xl font-extrabold tracking-tight">Takip ettiklerim</h1>
        <p class="text-ink-soft">Bunlarda bir şey olunca <a href="{{ route('notifications.edit') }}" class="underline decoration-section decoration-2 underline-offset-4">seçtiğin sıklıkta</a> e-posta alırsın.</p>
    </header>

    @if ($this->follows->isEmpty())
        <p class="font-hand text-2xl text-section-ink">Henüz bir şey takip etmiyorsun. Film, dizi, hedef ve projelerin sayfasında "takip et" düğmesi var.</p>
    @else
        <ul class="divide-y-2 divide-dashed divide-rule rounded-md border-2 border-rule">
            @foreach ($this->follows as $follow)
                @php($item = $this->describe($follow))
                <li wire:key="follow-{{ $follow->id }}" class="flex items-center justify-between gap-4 p-4">
                    <div class="min-w-0">
                        <p class="font-bold">
                            @if ($item['url'] && ! $item['muted'])
                                <a href="{{ $item['url'] }}" class="hover:text-section-ink">{{ $item['name'] }}</a>
                            @else
                                {{ $item['name'] }}
                            @endif
                        </p>
                        <p class="font-mono text-xs text-ink-faint">{{ $item['kind'] }}@if ($item['muted']) · şu an gizli, bildirim gelmez @endif</p>
                    </div>
                    <x-site.form.button variant="link" wire:click="unfollow({{ $follow->id }})">Takibi bırak</x-site.form.button>
                </li>
            @endforeach
        </ul>
    @endif
</div>
