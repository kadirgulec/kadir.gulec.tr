<?php

use Laravel\Fortify\Actions\ConfirmTwoFactorAuthentication;
use Laravel\Fortify\Actions\EnableTwoFactorAuthentication;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Locked;
use Livewire\Attributes\On;
use Livewire\Attributes\Validate;
use Livewire\Component;

new class extends Component {
    #[Locked]
    public bool $requiresConfirmation;

    #[Locked]
    public string $qrCodeSvg = '';

    #[Locked]
    public string $manualSetupKey = '';

    public bool $showVerificationStep = false;

    public bool $setupComplete = false;

    #[Validate('required|string|size:6', onUpdate: false)]
    public string $code = '';

    /**
     * Mount the component.
     */
    public function mount(bool $requiresConfirmation): void
    {
        $this->requiresConfirmation = $requiresConfirmation;
    }

    #[On('start-two-factor-setup')]
    public function startTwoFactorSetup(): void
    {
        $enableTwoFactorAuthentication = app(EnableTwoFactorAuthentication::class);
        $enableTwoFactorAuthentication(auth()->user());

        $this->loadSetupData();
    }

    /**
     * Load the two-factor authentication setup data for the user.
     */
    private function loadSetupData(): void
    {
        $user = auth()->user()?->fresh();

        try {
            if (! $user || ! $user->two_factor_secret) {
                throw new Exception('Two-factor setup secret is not available.');
            }

            $this->qrCodeSvg = $user->twoFactorQrCodeSvg();
            $this->manualSetupKey = decrypt($user->two_factor_secret);
        } catch (Exception) {
            $this->addError('setupData', 'Kurulum bilgileri alınamadı, sayfayı yenileyip tekrar dene.');

            $this->reset('qrCodeSvg', 'manualSetupKey');
        }
    }

    /**
     * Show the two-factor verification step if necessary.
     */
    public function showVerificationIfNecessary(): void
    {
        if ($this->requiresConfirmation) {
            $this->showVerificationStep = true;

            $this->resetErrorBag();

            return;
        }

        $this->closeModal();
        $this->dispatch('two-factor-enabled');
    }

    /**
     * Confirm two-factor authentication for the user.
     */
    public function confirmTwoFactor(ConfirmTwoFactorAuthentication $confirmTwoFactorAuthentication): void
    {
        $this->validate();

        $confirmTwoFactorAuthentication(auth()->user(), $this->code);

        $this->setupComplete = true;

        $this->closeModal();

        $this->dispatch('two-factor-enabled');
    }

    /**
     * Reset two-factor verification state.
     */
    public function resetVerification(): void
    {
        $this->reset('code', 'showVerificationStep');

        $this->resetErrorBag();
    }

    /**
     * Close the two-factor authentication modal.
     */
    public function closeModal(): void
    {
        $this->reset(
            'code',
            'manualSetupKey',
            'qrCodeSvg',
            'showVerificationStep',
            'setupComplete',
        );

        $this->resetErrorBag();

        $this->dispatch('modal-close', name: 'two-factor-setup-modal');
    }

    /**
     * Get the current modal configuration state.
     */
    #[Computed]
    public function modalConfig(): array
    {
        if ($this->setupComplete) {
            return [
                'title' => 'İki adımlı doğrulama açık',
                'description' => 'Bundan sonra girişte doğrulama uygulamandaki kod da sorulacak.',
                'buttonText' => 'Kapat',
            ];
        }

        if ($this->showVerificationStep) {
            return [
                'title' => 'Kodu doğrula',
                'description' => 'Doğrulama uygulamandaki 6 haneli kodu yaz.',
                'buttonText' => 'Devam et',
            ];
        }

        return [
            'title' => 'İki adımlı doğrulamayı aç',
            'description' => 'QR kodu doğrulama uygulamanla (ör. 1Password, Aegis, Google Authenticator) tara ya da anahtarı elle gir.',
            'buttonText' => 'Devam et',
        ];
    }
}; ?>

<div>
    <x-site.modal name="two-factor-setup-modal" x-on:close="$wire.closeModal()">
        <div class="space-y-2">
            <h2 class="pr-8 font-display text-2xl font-extrabold">{{ $this->modalConfig['title'] }}</h2>
            <p class="text-ink-soft">{{ $this->modalConfig['description'] }}</p>
        </div>

        @if ($showVerificationStep)
            <div class="space-y-5" x-data x-init="$nextTick(() => $el.querySelector('input')?.focus())">
                <x-site.form.input wire:model="code" name="code" label="Kod" inputmode="numeric" autocomplete="one-time-code" maxlength="6" class="[&_input]:text-center [&_input]:font-mono [&_input]:text-2xl [&_input]:tracking-[0.5em]" />

                <div class="flex flex-wrap items-center justify-between gap-4">
                    <x-site.form.button variant="link" wire:click="resetVerification">Geri</x-site.form.button>
                    <x-site.form.button wire:click="confirmTwoFactor" x-bind:disabled="$wire.code.length < 6">Onayla</x-site.form.button>
                </div>
            </div>
        @else
            <x-site.form.error :message="$errors->first('setupData')" />

            <div class="flex justify-center">
                <div class="grid size-56 place-items-center rounded-md bg-white p-3 shadow-sm">
                    @empty($qrCodeSvg)
                        <span class="font-hand text-xl text-ink-faint">yükleniyor…</span>
                    @else
                        {!! $qrCodeSvg !!}
                    @endempty
                </div>
            </div>

            @if ($manualSetupKey)
                <div
                    class="space-y-1.5"
                    x-data="{
                        copied: false,
                        async copy() {
                            try {
                                await navigator.clipboard.writeText(@js($manualSetupKey));
                                this.copied = true;
                                setTimeout(() => this.copied = false, 1500);
                            } catch (e) {}
                        },
                    }"
                >
                    <p class="text-sm font-bold">Ya da anahtarı elle gir</p>
                    <div class="flex items-stretch gap-2">
                        <code class="flex-1 rounded-md bg-paper-deep px-3 py-2 font-mono text-sm break-all select-all">{{ $manualSetupKey }}</code>
                        <x-site.form.button variant="secondary" x-on:click="copy()">
                            <span x-show="! copied">Kopyala</span>
                            <span x-show="copied" x-cloak>Kopyalandı</span>
                        </x-site.form.button>
                    </div>
                </div>
            @endif

            <x-site.form.button class="w-full" wire:click="showVerificationIfNecessary" :disabled="$errors->has('setupData')">{{ $this->modalConfig['buttonText'] }}</x-site.form.button>
        @endif
    </x-site.modal>
</div>
