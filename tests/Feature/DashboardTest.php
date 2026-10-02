<?php

use App\Enums\SystemRole;
use App\Models\User;

test('guests are redirected to the login page', function () {
    $this->get(route('admin.dashboard'))->assertRedirect(route('login'));
});

test('members do not learn that the admin panel exists', function () {
    $this->actingAs(User::factory()->member()->create())
        ->get(route('admin.dashboard'))
        ->assertNotFound();
});

test('the admin with two-factor authentication opens the dashboard', function () {
    $this->actingAs(User::factory()->admin()->create())
        ->get(route('admin.dashboard'))
        ->assertOk();
});

test('an admin who signed in with a password only is sent to the security settings', function () {
    config(['auth.admin_strong_login' => true]);
    $admin = User::factory()->withRole(SystemRole::Admin)->create();

    $this->actingAs($admin)
        ->get(route('admin.dashboard'))
        ->assertRedirect(route('security.edit'))
        ->assertSessionHas('status');
});

test('an admin whose session was verified with a passkey opens the dashboard', function () {
    config(['auth.admin_strong_login' => true]);
    $admin = User::factory()->withRole(SystemRole::Admin)->create();

    $this->actingAs($admin)
        ->withSession([User::PASSKEY_SESSION_KEY => $admin->id])
        ->get(route('admin.dashboard'))
        ->assertOk();
});

test('a passkey mark of another user does not count', function () {
    config(['auth.admin_strong_login' => true]);
    $admin = User::factory()->withRole(SystemRole::Admin)->create();

    $this->actingAs($admin)
        ->withSession([User::PASSKEY_SESSION_KEY => $admin->id + 1])
        ->get(route('admin.dashboard'))
        ->assertRedirect(route('security.edit'));
});

test('without the strong login rule a password login opens the dashboard', function () {
    config(['auth.admin_strong_login' => false]);

    $this->actingAs(User::factory()->withRole(SystemRole::Admin)->create())
        ->get(route('admin.dashboard'))
        ->assertOk();
});
