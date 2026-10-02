<?php

use App\Models\User;
use Livewire\Livewire;

it('shows validation errors under the admin form controls', function () {
    $this->actingAs(User::factory()->admin()->create());

    Livewire::test('pages::admin.styleguide')
        ->call('save')
        ->assertHasErrors(['title', 'body', 'agreed'])
        ->assertSee('Başlık alanı zorunlu.')
        ->assertSeeHtml('aria-invalid="true"');
});
