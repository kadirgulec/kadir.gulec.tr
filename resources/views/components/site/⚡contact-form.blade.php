<?php

use App\Actions\Contact\SendContactMessage;
use App\Rules\Honeypot;
use App\Rules\Turnstile;
use Livewire\Component;
use Symfony\Component\Mailer\Exception\TransportExceptionInterface;

/*
 * The contact form on the about page: name, e-mail and a message, sent to
 * Kadir by e-mail (see SendContactMessage). Nothing is stored on the site.
 */
new class extends Component {
    public string $name = '';

    public string $email = '';

    public string $message = '';

    public string $website = '';

    public string $turnstileToken = '';

    public ?string $status = null;

    public function mount(): void
    {
        $user = auth()->user();

        $this->name = $user->name ?? '';
        $this->email = $user->email ?? '';
    }

    public function send(SendContactMessage $send): void
    {
        $this->status = null;

        $this->validate([
            'name' => ['required', 'string', 'max:100'],
            'email' => ['required', 'email', 'max:255'],
            'message' => ['required', 'string', 'min:10', 'max:5000'],
            'website' => [new Honeypot],
            'turnstileToken' => [new Turnstile(request()->ip())],
        ], attributes: ['name' => 'ad', 'email' => 'e-posta', 'message' => 'mesaj']);

        try {
            $send->handle($this->name, $this->email, $this->message, request()->ip());
        } catch (TransportExceptionInterface $exception) {
            report($exception);
            $this->addError('message', 'Mesaj şu an gönderilemedi. Biraz sonra tekrar dene.');

            return;
        } finally {
            $this->turnstileToken = '';
            $this->dispatch('turnstile-reset');
        }

        $this->reset('message');
        $this->status = 'Mesajın ulaştı, teşekkürler! En kısa zamanda cevap yazarım.';
    }
}; ?>

<form wire:submit="send" class="space-y-4">
    <div class="grid gap-4 sm:grid-cols-2">
        <x-site.form.input wire:model="name" label="Adın" autocomplete="name" required />
        <x-site.form.input wire:model="email" type="email" label="E-postan" hint="Cevabı buraya yazarım." autocomplete="email" required />
    </div>

    <div class="space-y-1.5">
        <label for="contact-message" class="block text-sm font-bold text-ink">Mesajın</label>
        <textarea
            id="contact-message"
            wire:model="message"
            rows="5"
            required
            @error('message') aria-invalid="true" aria-describedby="contact-message-error" @enderror
            class="block w-full rounded-md border-2 border-rule bg-paper px-3 py-2.5 text-ink shadow-[inset_0_1px_2px_rgb(60_40_20/0.06)] transition placeholder:text-ink-faint focus:border-section-ink focus:outline-none aria-invalid:border-pen-red"
            placeholder="Merhaba Kadir…"
        ></textarea>
        <x-site.form.error :message="$errors->first('message')" id="contact-message-error" />
    </div>

    <x-site.form.error :message="$errors->first('website')" />
    <x-site.form.bot-check model="turnstileToken" />

    <div class="flex flex-wrap items-center gap-4">
        <x-site.form.button type="submit">Gönder</x-site.form.button>
        <span class="text-xs text-ink-faint">sitede saklanmaz, bana e-posta olarak gelir · <a href="{{ route('privacy') }}" class="underline">gizlilik</a></span>
    </div>

    <x-site.form.status :message="$status" />
</form>
