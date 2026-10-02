<?php

namespace App\Actions\Contact;

use App\Mail\ContactMessage;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Validation\ValidationException;

/**
 * Sends a message from the contact form to Kadir by e-mail. Nothing is
 * stored: the IP address only feeds a short-lived rate limit counter
 * (hashed), and "reply" in the mail program goes to the sender.
 */
class SendContactMessage
{
    public const PER_HOUR = 3;

    public const PER_DAY = 10;

    public function handle(string $name, string $email, string $message, ?string $ip): void
    {
        $this->throttle($ip);

        Mail::to(self::recipient())->send(new ContactMessage(trim($name), trim($email), trim($message)));
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
