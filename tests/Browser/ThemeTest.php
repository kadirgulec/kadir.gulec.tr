<?php

use App\Models\User;

/*
 * wire:navigate copies the new page's <html> attributes, which drops the "dark"
 * class the head script set on the first load.
 */
const IS_DARK = "document.documentElement.classList.contains('dark')";

it('stays dark when moving between account pages', function () {
    $this->actingAs(User::factory()->member()->create());

    visit('/hesap')
        ->inDarkMode()
        ->assertScript(IS_DARK, true)
        ->click('Bildirimler')
        ->assertPathIs('/hesap/bildirimler')
        ->assertScript(IS_DARK, true);
});

it('stays dark when the admin navigates with wire:navigate', function () {
    $this->actingAs(User::factory()->admin()->create());

    visit('/admin')
        ->inDarkMode()
        ->assertScript(IS_DARK, true)
        ->click('a[href$="/admin/yazilar"]')
        ->assertPathIs('/admin/yazilar')
        ->assertScript(IS_DARK, true);
});
