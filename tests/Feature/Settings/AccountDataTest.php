<?php

use App\Models\Comment;
use App\Models\Follow;
use App\Models\Post;
use App\Models\User;
use App\Models\Watchable;
use Livewire\Livewire;

it('downloads everything about the member as JSON without secrets', function () {
    $user = User::factory()->member()->withTwoFactor()->create(['name' => 'Ayşe']);
    $post = Post::factory()->create(['title' => 'Bir yazı']);
    Comment::factory()->for($user)->create(['commentable_id' => $post->id, 'body' => 'Benim yorumum']);
    $user->follows()->create(['followable_type' => 'watchable', 'followable_id' => Watchable::factory()->create(['title' => 'Dune'])->id]);

    $response = $this->actingAs($user)->get(route('account.export'));

    $response->assertOk()
        ->assertHeader('Content-Disposition', 'attachment; filename="kadir-gulec-tr-verilerim.json"')
        ->assertJsonPath('profile.name', 'Ayşe')
        ->assertJsonPath('comments.0.body', 'Benim yorumum')
        ->assertJsonPath('comments.0.post.title', 'Bir yazı')
        ->assertJsonPath('follows.0.name', 'Dune');

    expect($response->getContent())->not->toContain($user->password)->not->toContain('two_factor_secret');
});

it('keeps the comments of a deleted account, anonymised, and drops its follows', function () {
    $user = User::factory()->member()->create();
    $comment = Comment::factory()->for($user)->create();
    $user->follows()->create(['followable_type' => 'watchable', 'followable_id' => Watchable::factory()->create()->id]);

    $this->actingAs($user);
    Livewire::test('pages::settings.delete-user-modal')->set('password', 'password')->call('deleteUser')->assertHasNoErrors();

    expect($comment->fresh()->user_id)->toBeNull()
        ->and($comment->fresh()->authorName())->toBe('silinmiş üye')
        ->and(Follow::count())->toBe(0);
});
