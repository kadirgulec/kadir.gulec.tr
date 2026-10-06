<?php

use App\Models\User;

/** Whether the account deletion button can be seen (not just present in the HTML). */
const DELETE_BUTTON_SHOWN = "document.querySelector('[data-test=delete-user-button]').checkVisibility()";

it('keeps account deletion closed in the danger zone until it is opened on purpose', function () {
    $this->actingAs(User::factory()->create());

    $page = visit(route('security.edit'))
        ->type('password', 'password')
        ->press('Onayla')
        ->assertPathIs('/hesap/guvenlik')
        ->assertSee('Tehlikeli bölge')
        ->assertScript(DELETE_BUTTON_SHOWN, false);

    $page->click('Tehlikeli bölge')
        ->assertScript(DELETE_BUTTON_SHOWN, true)
        ->click('[data-test=delete-user-button]')
        ->assertSee('Hesabını silmek istediğine emin misin?');
});
