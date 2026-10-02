<?php

use Livewire\Component;

/*
 * "Tell me about new posts": one switch for every new post.
 */
new class extends Component {
    public function toggle(): void
    {
        $user = auth()->user();
        abort_unless($user?->hasVerifiedEmail(), 403);

        $user->forceFill(['notify_new_posts' => ! $user->notify_new_posts])->save();
    }
}; ?>

<div class="inline-flex">
    @guest
        <a href="{{ route('register') }}" class="font-hand text-xl text-section-ink underline decoration-wavy decoration-section underline-offset-4">yeni yazılardan haberdar olmak için kayıt ol</a>
    @else
        @if (auth()->user()->hasVerifiedEmail())
            <button type="button" wire:click="toggle" aria-pressed="{{ auth()->user()->notify_new_posts ? 'true' : 'false' }}" @class([
                'cursor-pointer rounded-sm border-2 border-section px-3 py-1 font-semibold',
                'bg-section text-section-on shadow-[2px_2px_0_rgb(0_0_0/0.15)]' => auth()->user()->notify_new_posts,
                'text-section-ink hover:bg-section hover:text-section-on' => ! auth()->user()->notify_new_posts,
            ])>
                {{ auth()->user()->notify_new_posts ? '✓ yeni yazılarda haber alıyorsun' : '+ yeni yazılarda haber ver' }}
            </button>
        @endif
    @endguest
</div>
