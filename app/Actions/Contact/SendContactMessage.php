<?php

namespace App\Actions\Contact;

use App\Enums\Permission;
use App\Mail\ContactMessageReceived;
use App\Models\ContactMessage;
use App\Support\Push\PushNotifier;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use Symfony\Component\Mailer\Exception\TransportExceptionInterface;

/**
 * Saves a message from the contact form to the admin inbox and mails it to
 * Kadir, with a push to the devices of whoever reads the messages. The
 * message is saved first, so a mail server hiccup loses nothing.
 * The IP address only feeds a short-lived rate limit counter (hashed).
 */
class SendContactMessage
{
    public const PER_HOUR = 3;

    public const PER_DAY = 10;

    public function handle(string $name, string $email, string $message, ?string $ip): ContactMessage
    {
        $this->throttle($ip);

        $contactMessage = ContactMessage::query()->create([
            'name' => trim($name),
            'email' => trim($email),
            'body' => trim($message),
        ]);

        try {
            Mail::to(self::recipient())->send(new ContactMessageReceived($contactMessage));
        } catch (TransportExceptionInterface $exception) {
            report($exception);
        }

        app(PushNotifier::class)->toPermitted(Permission::ReadMessages, [
            'title' => 'İletişim formu: '.$contactMessage->name,
            'body' => Str::limit($contactMessage->body, 140),
            'url' => route('admin.messages.index'),
            'tag' => 'contact-'.$contactMessage->id,
        ]);

        return $contactMessage;
    }

    public static function recipient(): string
    {
        return (string) (config('mail.contact_to') ?: config('legal.email'));
    }

    private function throttle(?string $ip): void
    {
        $sender = hash('sha256', (string) $ip);

        foreach (['hour' => [self::PER_HOUR, 3600], 'day' => [self::PER_DAY, 86400]] as $window => [$limit, $seconds]) {
            $key = 'contact:'.$window.':'.$sender;

            if (RateLimiter::tooManyAttempts($key, $limit)) {
                throw ValidationException::withMessages(['message' => 'Kısa sürede çok mesaj geldi. Biraz sonra tekrar dene.']);
            }

            RateLimiter::hit($key, $seconds);
        }
    }
}
