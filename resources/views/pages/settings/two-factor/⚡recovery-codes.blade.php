<?php

use Laravel\Fortify\Actions\GenerateNewRecoveryCodes;
use Livewire\Attributes\Locked;
use Livewire\Component;

new class extends Component {
    #[Locked]
    public array $recoveryCodes = [];

    /**
     * Mount the component.
     */
    public function mount(): void
    {
        $this->loadRecoveryCodes();
    }

    /**
     * Generate new recovery codes for the user.
     */
    public function regenerateRecoveryCodes(GenerateNewRecoveryCodes $generateNewRecoveryCodes): void
    {
        $generateNewRecoveryCodes(auth()->user());

        $this->loadRecoveryCodes();
    }

    /**
     * Load the recovery codes for the user.
     */
    private function loadRecoveryCodes(): void
    {
        $user = auth()->user();

        if ($user->hasEnabledTwoFactorAuthentication() && $user->two_factor_recovery_codes) {
            try {
                $this->recoveryCodes = json_decode(decrypt($user->two_factor_recovery_codes), true);
            } catch (Exception) {
                $this->addError('recoveryCodes', 'Failed to load recovery codes');

                $this->recoveryCodes = [];
            }
        }
    }
}; ?>

<div class="space-y-4 rounded-md border-2 border-dashed border-rule p-5" wire:cloak x-data="{ showRecoveryCodes: false }">
    <div class="space-y-1">
        <h3 class="font-bold">Kurtarma kodları</h3>
        <p class="text-sm text-ink-soft">Telefonunu kaybedersen bu kodlarla girersin. Bir şifre yöneticisinde sakla.</p>
    </div>

    <div class="flex flex-wrap items-center gap-4">
        <x-site.form.button variant="secondary" x-on:click="showRecoveryCodes = ! showRecoveryCodes" x-bind:aria-expanded="showRecoveryCodes" aria-controls="recovery-codes-section">
            <span x-show="! showRecoveryCodes">Kodları göster</span>
            <span x-show="showRecoveryCodes" x-cloak>Kodları gizle</span>
        </x-site.form.button>

        @if (filled($recoveryCodes))
            <x-site.form.button variant="link" x-show="showRecoveryCodes" x-cloak wire:click="regenerateRecoveryCodes">Yeni kodlar üret</x-site.form.button>
        @endif
    </div>

    <div x-show="showRecoveryCodes" x-cloak id="recovery-codes-section" class="space-y-3">
        <x-site.form.error :message="$errors->first('recoveryCodes')" />

        @if (filled($recoveryCodes))
            <ul class="grid gap-1 rounded-md bg-paper-deep p-4 font-mono text-sm sm:grid-cols-2" aria-label="Kurtarma kodları">
                @foreach ($recoveryCodes as $code)
                    <li class="select-all" wire:loading.class="opacity-50">{{ $code }}</li>
                @endforeach
            </ul>
            <p class="text-xs text-ink-faint">Her kod bir kere kullanılabilir. Biterse yenilerini üret.</p>
        @endif
    </div>
</div>
