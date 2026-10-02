<?php

use App\Enums\SystemRole;
use Laravel\Fortify\Features;

beforeEach(function () {
    $this->skipUnlessFortifyHas(Features::registration());
});

test('registration screen can be rendered', function () {
    $response = $this->get(route('register'));

    $response->assertOk();
});

test('new users can register', function () {
    $response = $this->post(route('register.store'), [
        'name' => 'John Doe',
        'email' => 'test@example.com',
        'password' => 'password',
        'password_confirmation' => 'password',
    ]);

    $response->assertSessionHasNoErrors()
        ->assertRedirect(route('home', absolute: false));

    $this->assertAuthenticated();
    expect(auth()->user()->hasRole(SystemRole::Member->value))->toBeTrue();
});

test('bots that fill the honeypot cannot register', function () {
    $this->post(route('register.store'), [
        'name' => 'Bot',
        'email' => 'bot@example.com',
        'password' => 'password',
        'password_confirmation' => 'password',
        'website' => 'http://spam.example',
    ])->assertSessionHasErrors('website');

    $this->assertGuest();
});

test('a registration without a Turnstile token fails once a secret is configured', function () {
    config(['services.turnstile.secret_key' => 'secret']);

    $this->post(route('register.store'), [
        'name' => 'Bot',
        'email' => 'bot@example.com',
        'password' => 'password',
        'password_confirmation' => 'password',
    ])->assertSessionHasErrors('cf-turnstile-response');

    $this->assertGuest();
});
