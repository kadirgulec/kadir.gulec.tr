<?php

use App\Models\ContactMessage;
use App\Models\User;
use Livewire\Livewire;

it('lists the messages for the admin, newest first, with the unread count in the sidebar', function () {
    ContactMessage::factory()->read()->create(['name' => 'Eski Okunmuş', 'created_at' => now()->subDay()]);
    ContactMessage::factory()->create(['name' => 'Yeni Gelen', 'body' => 'Merhaba!']);

    $this->actingAs(User::factory()->admin()->create())
        ->get(route('admin.messages.index'))
        ->assertOk()
        ->assertSeeTextInOrder(['Mesajlar', '1', 'Yeni Gelen', 'Merhaba!', 'Eski Okunmuş']);
});

it('filters the unread ones, marks them read or unread and deletes them', function () {
    $message = ContactMessage::factory()->create(['name' => 'Ayşe']);
    ContactMessage::factory()->read()->create(['name' => 'Mehmet']);
    $this->actingAs(User::factory()->admin()->create());

    $page = Livewire::test('pages::admin.messages.index')
        ->set('filter', 'okunmamis')
        ->assertSee('Ayşe')
        ->assertDontSee('Mehmet');

    $page->call('markRead', $message->id)->assertDontSee('Ayşe');
    expect($message->fresh()->isRead())->toBeTrue();

    $page->call('markUnread', $message->id)->assertSee('Ayşe');
    expect($message->fresh()->isRead())->toBeFalse();

    $page->call('delete', $message->id);
    expect($message->fresh())->toBeNull();
});

it('hides the inbox from members', function () {
    $this->actingAs(User::factory()->member()->create())
        ->get(route('admin.messages.index'))
        ->assertNotFound();
});
