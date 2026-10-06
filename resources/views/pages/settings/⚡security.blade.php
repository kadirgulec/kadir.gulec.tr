<?php

use App\Concerns\PasswordValidationRules;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\ValidationException;
use Laravel\Fortify\Actions\DisableTwoFactorAuthentication;
use Laravel\Fortify\Features;
use Laravel\Fortify\Fortify;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;
use Laravel\Passkeys\Actions\DeletePasskey;
use Livewire\Attributes\Locked;
use Livewire\Attributes\On;

new #[Layout('layouts::account'), Title('Güvenlik')] class extends Component {
    use PasswordValidationRules;

    public string $current_password = '';
    public string $password = '';
    public string $password_confirmation = '';

    public bool $canManageTwoFactor;

    public bool $twoFactorEnabled;

    public bool $requiresConfirmation;

    #[Locked]
    public bool $canManagePasskeys;

    #[Locked]
    public array $passkeys = [];

    public ?string $passwordStatus = null;

    #[Locked]
    public ?int $deletingPasskeyId = null;

    #[Locked]
    public string $deletingPasskeyName = '';

    /**
     * Mount the component.
     */
    public function mount(DisableTwoFactorAuthentication $disableTwoFactorAuthentication): void
    {
        $this->canManageTwoFactor = Features::canManageTwoFactorAuthentication();

        if ($this->canManageTwoFactor) {
            if (Fortify::confirmsTwoFactorAuthentication() && is_null(auth()->user()->two_factor_confirmed_at)) {
                $disableTwoFactorAuthentication(auth()->user());
            }

            $this->twoFactorEnabled = auth()->user()->hasEnabledTwoFactorAuthentication();
            $this->requiresConfirmation = Features::optionEnabled(Features::twoFactorAuthentication(), 'confirm');
        }

        $this->canManagePasskeys = Features::canManagePasskeys();

        if ($this->canManagePasskeys) {
            $this->loadPasskeys();
        }
    }

    /**
     * Update the password for the currently authenticated user.
     */
    public function updatePassword(): void
    {
        try {
            $validated = $this->validate([
                'current_password' => $this->currentPasswordRules(),
                'password' => $this->passwordRules(),
            ]);
        } catch (ValidationException $e) {
            $this->reset('current_password', 'password', 'password_confirmation');

            throw $e;
        }

        Auth::user()->update([
            'password' => $validated['password'],
        ]);

        $this->reset('current_password', 'password', 'password_confirmation');

        $this->passwordStatus = 'Şifren değişti.';
    }

    /**
     * Load the user's passkeys.
     */
    public function loadPasskeys(): void
    {
        $this->passkeys = auth()->user()->passkeys()
            ->select(['id', 'name', 'credential', 'created_at', 'last_used_at'])
            ->latest()
            ->get()
            ->map(fn ($passkey) => [
                'id' => $passkey->id,
                'name' => $passkey->name,
                'authenticator' => $passkey->authenticator,
                'created_at_diff' => $passkey->created_at->diffForHumans(),
                'last_used_at_diff' => $passkey->last_used_at?->diffForHumans(),
            ])
            ->toArray();
    }

    /**
     * Show the delete confirmation modal.
     */
    public function confirmDelete(int $passkeyId): void
    {
        $passkey = auth()->user()->passkeys()->findOrFail($passkeyId);

        $this->deletingPasskeyId = $passkey->id;
        $this->deletingPasskeyName = $passkey->name;
        $this->dispatch('modal-show', name: 'delete-passkey-modal');
    }

    /**
     * Delete the passkey.
     */
    public function deletePasskey(DeletePasskey $deletePasskey): void
    {
        if (! $this->deletingPasskeyId) {
            return;
        }

        $passkey = auth()->user()->passkeys()->findOrFail($this->deletingPasskeyId);

        $deletePasskey(auth()->user(), $passkey);

        $this->closeDeleteModal();
        $this->loadPasskeys();
    }

    /**
     * Close the delete confirmation modal.
     */
    public function closeDeleteModal(): void
    {
        $this->dispatch('modal-close', name: 'delete-passkey-modal');
        $this->deletingPasskeyId = null;
        $this->deletingPasskeyName = '';
    }

    /**
     * Handle the two-factor authentication enabled event.
     */
    #[On('two-factor-enabled')]
    public function onTwoFactorEnabled(): void
    {
        $this->twoFactorEnabled = true;
    }

    /**
     * Disable two-factor authentication for the user.
     */
    public function disable(DisableTwoFactorAuthentication $disableTwoFactorAuthentication): void
    {
        $disableTwoFactorAuthentication(auth()->user());

        $this->twoFactorEnabled = false;
    }
}; ?>

<div class="space-y-12">
    <header class="space-y-1">
        <h1 class="font-display text-4xl font-extrabold tracking-tight">Güvenlik</h1>
        <p class="text-ink-soft">Şifren, iki adımlı doğrulama, passkey'lerin ve hesabını silme.</p>
    </header>

    <x-site.form.status :message="session('status')" />

    <section class="space-y-5">
        <h2 class="font-display text-2xl font-extrabold">Şifreyi değiştir</h2>

        <form method="POST" wire:submit="updatePassword" class="space-y-5">
            <x-site.form.input wire:model="current_password" label="Şimdiki şifre" type="password" required autocomplete="current-password" viewable />
            <x-site.form.input wire:model="password" label="Yeni şifre" type="password" required autocomplete="new-password" passwordrules="{{ \Illuminate\Validation\Rules\Password::defaults()->toPasswordRulesString() }}" viewable />
            <x-site.form.input wire:model="password_confirmation" label="Yeni şifre tekrar" type="password" required autocomplete="new-password" passwordrules="{{ \Illuminate\Validation\Rules\Password::defaults()->toPasswordRulesString() }}" viewable />

            <div class="flex items-center gap-4">
                <x-site.form.button type="submit" data-test="update-password-button">Kaydet</x-site.form.button>
                <x-site.form.status :message="$passwordStatus" />
            </div>
        </form>
    </section>

    @if ($canManageTwoFactor)
        <section class="space-y-5 border-t-2 border-dashed border-rule pt-10" wire:cloak>
            <div class="space-y-1">
                <h2 class="font-display text-2xl font-extrabold">İki adımlı doğrulama</h2>
                <p class="text-ink-soft">
                    @if ($twoFactorEnabled)
                        Açık. Girişte şifrenden sonra telefonundaki doğrulama uygulamasının ürettiği kod da sorulur.
                    @else
                        Kapalı. Açarsan girişte şifrenden sonra telefonundaki doğrulama uygulamasının ürettiği kod da sorulur.
                    @endif
                </p>
            </div>

            @if ($twoFactorEnabled)
                <x-site.form.button variant="danger" wire:click="disable">İki adımlı doğrulamayı kapat</x-site.form.button>

                <livewire:pages::settings.two-factor.recovery-codes :$requiresConfirmation />
            @else
                <x-site.form.button x-data x-on:click="$dispatch('modal-show', { name: 'two-factor-setup-modal' }); $wire.dispatch('start-two-factor-setup')">İki adımlı doğrulamayı aç</x-site.form.button>

                <livewire:pages::settings.two-factor-setup-modal :requires-confirmation="$requiresConfirmation" />
            @endif
        </section>
    @endif

    @if ($canManagePasskeys)
        <section class="space-y-5 border-t-2 border-dashed border-rule pt-10" wire:cloak>
            <div class="space-y-1">
                <h2 class="font-display text-2xl font-extrabold">Passkey'ler</h2>
                <p class="text-ink-soft">Şifresiz, parmak izi ya da yüz tanımayla giriş.</p>
            </div>

            <ul class="divide-y-2 divide-dashed divide-rule rounded-md border-2 border-rule">
                @forelse ($passkeys as $passkey)
                    <li class="flex items-center justify-between gap-4 p-4" wire:key="passkey-{{ $passkey['id'] }}">
                        <div class="min-w-0 space-y-0.5">
                            <p class="font-bold">
                                {{ $passkey['name'] }}
                                @if ($passkey['authenticator'])
                                    <span class="ml-1 font-mono text-xs font-normal text-ink-faint">{{ $passkey['authenticator'] }}</span>
                                @endif
                            </p>
                            <p class="font-mono text-xs text-ink-faint">
                                eklendi: {{ $passkey['created_at_diff'] }}
                                @if ($passkey['last_used_at_diff'])
                                    · son kullanım: {{ $passkey['last_used_at_diff'] }}
                                @endif
                            </p>
                        </div>

                        <x-site.form.button variant="link" wire:click="confirmDelete({{ $passkey['id'] }})" class="!text-pen-red">Kaldır</x-site.form.button>
                    </li>
                @empty
                    <li class="p-6 text-center">
                        <p class="font-bold">Henüz passkey yok</p>
                        <p class="text-sm text-ink-soft">Bir passkey ekleyince şifresiz girebilirsin.</p>
                    </li>
                @endforelse
            </ul>

            <x-passkey-registration />
        </section>
    @endif

    {{-- Kept here, at the very end and behind the password confirmation, rather than on the profile. --}}
    <livewire:pages::settings.delete-user-form />

    <x-site.modal name="delete-passkey-modal" heading="Passkey kaldırılsın mı?" x-on:close="$wire.closeDeleteModal()">
        <p class="text-ink-soft">"{{ $deletingPasskeyName }}" ile artık giriş yapamazsın.</p>

        <div class="flex flex-wrap items-center justify-end gap-4">
            <x-site.form.button variant="link" wire:click="closeDeleteModal">Vazgeç</x-site.form.button>
            <x-site.form.button variant="danger" wire:click="deletePasskey">Kaldır</x-site.form.button>
        </div>
    </x-site.modal>
</div>
