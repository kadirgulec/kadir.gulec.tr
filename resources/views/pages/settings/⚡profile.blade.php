<?php

use App\Concerns\ProfileValidationRules;
use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Session;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;

new #[Layout('layouts::account'), Title('Profil')] class extends Component {
    use ProfileValidationRules;

    public string $name = '';

    public string $email = '';

    public ?string $status = null;

    /**
     * Mount the component.
     */
    public function mount(): void
    {
        $this->name = Auth::user()->name;
        $this->email = Auth::user()->email;
    }

    /**
     * Update the profile information for the currently authenticated user.
     */
    public function updateProfileInformation(): void
    {
        $user = Auth::user();

        $validated = $this->validate($this->profileRules($user->id));

        $user->fill($validated);

        if ($user->isDirty('email')) {
            $user->email_verified_at = null;
        }

        $user->save();

        $this->status = 'Kaydedildi.';
    }

    /**
     * Send an email verification notification to the current user.
     */
    public function resendVerificationNotification(): void
    {
        $user = Auth::user();

        if ($user->hasVerifiedEmail()) {
            $this->redirectIntended(default: route('home', absolute: false));

            return;
        }

        $user->sendEmailVerificationNotification();

        Session::flash('status', 'verification-link-sent');
    }

    #[Computed]
    public function hasUnverifiedEmail(): bool
    {
        return Auth::user() instanceof MustVerifyEmail && ! Auth::user()->hasVerifiedEmail();
    }

    #[Computed]
    public function showDeleteUser(): bool
    {
        return ! Auth::user() instanceof MustVerifyEmail
            || (Auth::user() instanceof MustVerifyEmail && Auth::user()->hasVerifiedEmail());
    }
}; ?>

<div class="space-y-12">
    <section class="space-y-6">
        <header class="space-y-1">
            <h1 class="font-display text-4xl font-extrabold tracking-tight">Profil</h1>
            <p class="text-ink-soft">Adın yorumlarının yanında görünür, e-postan kimseye gösterilmez.</p>
        </header>

        <form wire:submit="updateProfileInformation" class="space-y-5">
            <x-site.form.input wire:model="name" label="Görünen ad" required autocomplete="name" />

            <div class="space-y-3">
                <x-site.form.input wire:model="email" label="E-posta" type="email" required autocomplete="email" />

                @if ($this->hasUnverifiedEmail)
                    <p class="text-sm text-ink-soft">
                        E-posta adresin henüz doğrulanmadı.
                        <button type="button" wire:click.prevent="resendVerificationNotification" class="cursor-pointer font-semibold underline decoration-section decoration-2 underline-offset-4 hover:text-ink">Doğrulama linkini tekrar gönder.</button>
                    </p>

                    @if (session('status') === 'verification-link-sent')
                        <x-site.form.status message="Yeni bir doğrulama linki gönderdik." />
                    @endif
                @endif
            </div>

            <div class="flex items-center gap-4">
                <x-site.form.button type="submit" data-test="update-profile-button">Kaydet</x-site.form.button>
                <x-site.form.status :message="$status" />
            </div>
        </form>
    </section>

    <section class="space-y-3 border-t-2 border-dashed border-rule pt-10">
        <h2 class="font-display text-2xl font-extrabold">Verilerim</h2>
        <p class="text-ink-soft">Hesabın, yorumların ve takiplerin tek bir JSON dosyasında. Şifre ve güvenlik anahtarları dosyada yer almaz.</p>
        <x-site.form.button variant="secondary" :href="route('account.export')">Verilerimi indir</x-site.form.button>
    </section>

    @if ($this->showDeleteUser)
        <livewire:pages::settings.delete-user-form />
    @endif
</div>
