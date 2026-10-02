<?php

namespace App\Rules;

use Closure;
use Illuminate\Contracts\Validation\ValidationRule;

/**
 * A field people never see (it is hidden with CSS and skipped by screen
 * readers), but bots fill in. Any value fails silently-ish.
 */
class Honeypot implements ValidationRule
{
    public const FIELD = 'website';

    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        if (filled($value)) {
            $fail('Bir şeyler ters gitti, formu tekrar gönder.');
        }
    }
}
