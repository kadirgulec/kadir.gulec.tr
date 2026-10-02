<?php

namespace App\Actions\Contact;

use App\Mail\ContactMessageReceived;
use App\Models\ContactMessage;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Validation\ValidationException;
use Symfony\Component\Mailer\Exception\TransportExceptionInterface;

/**
 * Saves a message from the contact form to the admin inbox and mails it to
 * Kadir. The message is saved first, so a mail server hiccup loses nothing.
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
