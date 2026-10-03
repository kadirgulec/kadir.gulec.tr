<?php

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rules\Password;

function passesPasswordRules(string $password): bool
{
    return Validator::make(['password' => $password], ['password' => Password::defaults()])->passes();
}

it('asks for 8 characters with mixed case, a number and a symbol in production', function () {
    app()->detectEnvironment(fn (): string => 'production');
    Http::fake(['api.pwnedpasswords.com/*' => Http::response('')]);

    expect(passesPasswordRules('Defter1!'))->toBeTrue()
        ->and(passesPasswordRules('Defte1!'))->toBeFalse()
        ->and(passesPasswordRules('defter1!'))->toBeFalse()
        ->and(passesPasswordRules('Defterim!'))->toBeFalse()
        ->and(passesPasswordRules('Defterim1'))->toBeFalse();
});

it('only asks for 8 characters outside production', function () {
    expect(passesPasswordRules('defterim'))->toBeTrue()
        ->and(passesPasswordRules('defter'))->toBeFalse();
});
