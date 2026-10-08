<?php

use Livewire\Attributes\Computed;
use Livewire\Attributes\Locked;
use Livewire\Component;

/*
 * "Tell me about new posts / notes": one switch per kind. New posts arrive
 * one by one, new notes in the digest.
 */
new class extends Component {
    /** Each kind's column on the user and its copy. */
    private const KINDS = [
        'posts' => [
            'column' => 'notify_new_posts',
            'register' => 'yeni yazılardan haberdar olmak için kayıt ol',
            'on' => '✓ yeni yazılarda haber alıyorsun',
            'off' => '+ yeni yazılarda haber ver',
        ],
        'notes' => [
            'column' => 'notify_new_notes',
            'register' => 'yeni notları özetle almak için kayıt ol',
            'on' => '✓ yeni notlar özetinde',
            'off' => '+ yeni notları özetimde gönder',
        ],
    ];

    #[Locked]
    public string $kind;

    public function mount(string $kind): void
    {
        abort_unless(array_key_exists($kind, self::KINDS), 404);

        $this->kind = $kind;
    }

    /**
     * @return array{column: string, register: string, on: string, off: string}
     */
    #[Computed]
    public function copy(): array
    {
        return self::KINDS[$this->kind];
    }

    public function toggle(): void
    {
        $user = auth()->user();
        abort_unless($user?->hasVerifiedEmail(), 403);

        $column = $this->copy['column'];
        $user->forceFill([$column => ! $user->{$column}])->save();
    }
}; ?>

<div class="inline-flex">
    @guest
        <a href="{{ route('register') }}" class="font-hand text-xl text-section-ink underline decoration-wavy decoration-section underline-offset-6 [text-decoration-skip-ink:none]">{{ $this->copy['register'] }}</a>
    @else
        @if (auth()->user()->hasVerifiedEmail())
            @php($isOn = (bool) auth()->user()->{$this->copy['column']})
            <button type="button" wire:click="toggle" aria-pressed="{{ $isOn ? 'true' : 'false' }}" @class([
                'cursor-pointer rounded-sm border-2 border-section px-3 py-1 font-semibold',
                'bg-section text-section-on shadow-[2px_2px_0_rgb(0_0_0/0.15)]' => $isOn,
                'text-section-ink hover:bg-section hover:text-section-on' => ! $isOn,
            ])>
                {{ $isOn ? $this->copy['on'] : $this->copy['off'] }}
            </button>
        @endif
    @endguest
</div>
