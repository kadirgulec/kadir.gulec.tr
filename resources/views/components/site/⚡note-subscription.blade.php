<?php

use Livewire\Component;

/*
 * "Tell me about new notes": one switch, the notes arrive in the digest.
 */
new class extends Component {
    public function toggle(): void
    {
        $user = auth()->user();
        abort_unless($user?->hasVerifiedEmail(), 403);

        $user->forceFill(['notify_new_notes' => ! $user->notify_new_notes])->save();
    }
}; ?>

<div class="inline-flex">
    @guest
        <a href="{{ route('register') }}" class="font-hand text-xl text-section-ink underline decoration-wavy decoration-section underline-offset-6 [text-decoration-skip-ink:none]">yeni notları özetle almak için kayıt ol</a>
    @else
        @if (auth()->user()->hasVerifiedEmail())
            <button type="button" wire:click="toggle" aria-pressed="{{ auth()->user()->notify_new_notes ? 'true' : 'false' }}" @class([
                'cursor-pointer rounded-sm border-2 border-section px-3 py-1 font-semibold',
                'bg-section text-section-on shadow-[2px_2px_0_rgb(0_0_0/0.15)]' => auth()->user()->notify_new_notes,
                'text-section-ink hover:bg-section hover:text-section-on' => ! auth()->user()->notify_new_notes,
            ])>
                {{ auth()->user()->notify_new_notes ? '✓ yeni notlar özetinde' : '+ yeni notları özetimde gönder' }}
            </button>
        @endif
    @endguest
</div>
