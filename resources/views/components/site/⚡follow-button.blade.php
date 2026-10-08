<?php

use App\Enums\Permission;
use App\Models\Goal;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\Relation;
use Livewire\Attributes\Locked;
use Livewire\Component;

/*
 * "Takip et" toggle for a film/series, a goal or a project. Guests are
 * asked to sign in; hidden goals cannot be followed.
 */
new class extends Component {
    #[Locked]
    public string $type;

    #[Locked]
    public int $id;

    /** The followable kinds (morph names). */
    private const TYPES = ['watchable', 'goal', 'project'];

    public function mount(string $type, int $id): void
    {
        abort_unless(in_array($type, self::TYPES, true), 404);

        $this->type = $type;
        $this->id = $id;
    }

    public function toggle(): void
    {
        $user = auth()->user();
        abort_unless($user?->can(Permission::Follow->value) && $user->hasVerifiedEmail(), 403);

        $followable = $this->followable();
        abort_if($followable instanceof Goal && ! $followable->visibility->isVisible(), 404);

        $existing = $user->follows()->where('followable_type', $this->type)->where('followable_id', $this->id)->first();

        if ($existing) {
            $existing->delete();
        } else {
            $user->follows()->create(['followable_type' => $this->type, 'followable_id' => $this->id]);
        }
    }

    private function followable(): Model
    {
        abort_unless(in_array($this->type, self::TYPES, true), 404);
        $class = Relation::getMorphedModel($this->type) ?? abort(404);

        return $class::query()->findOrFail($this->id);
    }

    public function isFollowing(): bool
    {
        return auth()->check() && auth()->user()->follows()->where('followable_type', $this->type)->where('followable_id', $this->id)->exists();
    }
}; ?>

<div class="inline-flex items-center gap-2">
    @guest
        <a href="{{ route('login') }}" class="font-hand text-xl text-section-ink underline decoration-wavy decoration-section underline-offset-4">takip etmek için giriş yap</a>
    @else
        @if (auth()->user()->can(\App\Enums\Permission::Follow->value) && auth()->user()->hasVerifiedEmail())
            @if ($this->isFollowing())
                <button type="button" wire:click="toggle" aria-pressed="true" class="cursor-pointer rounded-sm border-2 border-section bg-section px-3 py-1 font-semibold text-section-on shadow-[2px_2px_0_rgb(0_0_0/0.15)]">✓ takip ediyorsun</button>
            @else
                <button type="button" wire:click="toggle" aria-pressed="false" class="cursor-pointer rounded-sm border-2 border-section px-3 py-1 font-semibold text-section-ink hover:bg-section hover:text-section-on">+ takip et</button>
            @endif
        @endif
    @endguest
</div>
