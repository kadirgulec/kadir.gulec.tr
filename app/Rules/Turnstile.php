<?php

namespace App\Rules;

use Closure;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Support\Facades\Http;
use Throwable;

/**
 * Checks a Cloudflare Turnstile token with the siteverify API. Tokens are
 * single-use and expire after five minutes. Without a configured secret the
 * check is skipped (e.g. a fresh local checkout).
 */
class Turnstile implements ValidationRule
{
    private const VERIFY_URL = 'https://challenges.cloudflare.com/turnstile/v0/siteverify';

    /**
     * Run on empty and missing values too: otherwise the validator skips the
     * rule and a form sent without a token passes.
     */
    public bool $implicit = true;

    public function __construct(private ?string $ip = null) {}

    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        $secret = config('services.turnstile.secret_key');

        if (blank($secret)) {
            return;
        }

        if (! is_string($value) || $value === '') {
            $fail('Robot olmadığını doğrulayamadık. Kutucuğun yüklenmesini bekleyip tekrar dene.');

            return;
        }

        try {
            $passed = Http::asForm()->timeout(5)->post(self::VERIFY_URL, array_filter([
                'secret' => $secret,
                'response' => $value,
                'remoteip' => $this->ip,
            ]))->json('success') === true;
        } catch (Throwable $exception) {
            report($exception);
            $passed = false;
        }

        if (! $passed) {
            $fail('Robot olmadığını doğrulayamadık. Sayfayı yenileyip tekrar dene.');
        }
    }
}
