<?php

use App\Concerns\PasswordValidationRules;
use App\Livewire\Actions\Logout;
use Illuminate\Support\Facades\Auth;
use Livewire\Component;

new class extends Component {
    use PasswordValidationRules;

    public string $password = '';

    /**
     * Delete the currently authenticated user.
     */
    public function deleteUser(Logout $logout): void
    {
        $this->validate([
            'password' => $this->currentPasswordRules(),
        ]);

        tap(Auth::user(), $logout(...))->delete();

        $this->redirect('/');
    }
}; ?>

<div>
    <x-site.modal name="confirm-user-deletion" heading="Hesabını silmek istediğine emin misin?">
        <form method="POST" wire:submit="deleteUser" class="space-y-5">
            <p class="text-ink-soft">Bu işlem geri alınamaz. Onaylamak için şifreni yaz.</p>

            <x-site.form.input wire:model="password" label="Şifre" type="password" autocomplete="current-password" viewable />

            <div class="flex flex-wrap items-center justify-end gap-4">
                <x-site.form.button variant="link" x-on:click="$el.closest('dialog').close()">Vazgeç</x-site.form.button>
                <x-site.form.button variant="danger" type="submit" data-test="confirm-delete-user-button">Hesabımı sil</x-site.form.button>
            </div>
        </form>
    </x-site.modal>
</div>
