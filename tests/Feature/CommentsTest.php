<?php

use App\Actions\Comments\PostComment;
use App\Models\Comment;
use App\Models\Post;
use App\Models\User;
use Illuminate\Support\Facades\Http;
use Illuminate\Validation\ValidationException;
use Livewire\Features\SupportTesting\Testable;
use Livewire\Livewire;

beforeEach(function () {
    $this->post = Post::factory()->create();
});

function commentsOn(Post $post): Testable
{
    return Livewire::test('site.comments', ['post' => $post]);
}

it('keeps the first comment of a member waiting and shows it only to its author', function () {
    $member = User::factory()->member()->create(['name' => 'Ayşe']);

    $this->actingAs($member);
    commentsOn($this->post)->set('body', 'İlk yorumum.')->call('post')->assertHasNoErrors()
        ->assertSee('İlk yorumum.')
        ->assertSee('onay bekliyor')
        ->assertSee('Kadir onaylayınca görünecek');

    expect(Comment::sole()->isApproved())->toBeFalse();

    auth()->logout();
    $this->get(route('posts.show', $this->post->slug))->assertDontSeeText('İlk yorumum.');
});

it('publishes later comments of a member with an approved one right away', function () {
    $member = User::factory()->member()->create();
    Comment::factory()->for($member)->create(['commentable_id' => $this->post->id]);

    $this->actingAs($member);
    commentsOn($this->post)->set('body', 'İkinci yorum.')->call('post');

    expect(Comment::query()->latest('id')->first()->isApproved())->toBeTrue();
});

it('approves the comments of the admin right away', function () {
    $this->actingAs(User::factory()->admin()->create());
    commentsOn($this->post)->set('body', 'Teşekkürler!')->call('post');

    expect(Comment::sole()->isApproved())->toBeTrue();
});

it('puts a reply to a reply under the same top comment', function () {
    $top = Comment::factory()->create(['commentable_id' => $this->post->id]);
    $reply = Comment::factory()->create(['commentable_id' => $this->post->id, 'parent_id' => $top->id]);
    $member = User::factory()->member()->create();
    Comment::factory()->for($member)->create(['commentable_id' => $this->post->id]);

    $this->actingAs($member);
    commentsOn($this->post)->call('startReply', $top->id)->set('replyBody', 'Katılıyorum.')->call('reply')->assertHasNoErrors();

    expect(Comment::query()->latest('id')->first()->parent_id)->toBe($top->id)
        ->and($reply->fresh()->parent_id)->toBe($top->id);
});

it('lets the author edit within the window only', function () {
    $member = User::factory()->member()->create();
    $comment = Comment::factory()->for($member)->create(['commentable_id' => $this->post->id, 'body' => 'Yanlış yazdım']);
    $this->actingAs($member);

    commentsOn($this->post)->call('startEdit', $comment->id)->set('editBody', 'Doğru yazdım')->call('saveEdit')->assertHasNoErrors();
    expect($comment->fresh()->body)->toBe('Doğru yazdım')->and($comment->fresh()->edited_at)->not->toBeNull();

    $this->travel(Comment::EDIT_WINDOW + 1)->minutes();
    commentsOn($this->post)->call('startEdit', $comment->id)->assertForbidden();
});

it('keeps a deleted comment with replies as "silindi" and removes one without', function () {
    $member = User::factory()->member()->create();
    $withReplies = Comment::factory()->for($member)->create(['commentable_id' => $this->post->id, 'body' => 'Silinecek ana not']);
    Comment::factory()->create(['commentable_id' => $this->post->id, 'parent_id' => $withReplies->id, 'body' => 'Kalan cevap']);
    $alone = Comment::factory()->for($member)->create(['commentable_id' => $this->post->id]);
    $this->actingAs($member);

    commentsOn($this->post)->call('delete', $withReplies->id)->call('delete', $alone->id)
        ->assertSee('bu not silindi')
        ->assertSee('Kalan cevap')
        ->assertDontSee('Silinecek ana not');

    $this->assertSoftDeleted($withReplies);
    $this->assertModelMissing($alone);
});

it('does not let one member delete the comment of another', function () {
    $comment = Comment::factory()->create(['commentable_id' => $this->post->id]);
    $this->actingAs(User::factory()->member()->create());

    commentsOn($this->post)->call('delete', $comment->id)->assertForbidden();
});

it('refuses comments from blocked or unverified users and hides those of blocked users', function () {
    $blocked = User::factory()->member()->blocked()->create();
    Comment::factory()->for($blocked)->create(['commentable_id' => $this->post->id, 'body' => 'Spam linkler']);

    $this->get(route('posts.show', $this->post->slug))->assertDontSeeText('Spam linkler');

    $this->actingAs($blocked);
    commentsOn($this->post)->assertSee('Bu hesapla yorum yazılamıyor.');
    expect(fn () => app(PostComment::class)->handle($blocked, $this->post, 'tekrar'))->toThrow(ValidationException::class);

    $this->actingAs(User::factory()->member()->unverified()->create());
    commentsOn($this->post)->assertSee('e-postanı doğrula');
});

it('slows down members who comment too fast', function () {
    $member = User::factory()->member()->create();
    Comment::factory()->for($member)->create(['commentable_id' => $this->post->id]);
    $this->actingAs($member);
    $component = commentsOn($this->post);

    foreach (range(1, 5) as $i) {
        $component->set('body', "yorum $i")->call('post')->assertHasNoErrors();
    }

    $component->set('body', 'altıncı')->call('post')->assertHasErrors('body');
});

it('stops bots that fill the honeypot', function () {
    $this->actingAs(User::factory()->member()->create());

    commentsOn($this->post)->set('website', 'http://spam.example')->set('body', 'Selam')->call('post')->assertHasErrors('website');

    expect(Comment::count())->toBe(0);
});

it('checks the Turnstile token when a secret is configured', function () {
    config(['services.turnstile.secret_key' => 'secret']);
    Http::fake(['challenges.cloudflare.com/*' => Http::sequence()->push(['success' => false])->push(['success' => true])]);
    $this->actingAs(User::factory()->member()->create());

    commentsOn($this->post)->set('body', 'Selam')->call('post')->assertHasErrors('turnstileToken');
    commentsOn($this->post)->set('body', 'Selam')->set('turnstileToken', 'bad')->call('post')->assertHasErrors('turnstileToken');
    commentsOn($this->post)->set('body', 'Selam')->set('turnstileToken', 'good')->call('post')->assertHasNoErrors();

    Http::assertSent(fn ($request) => $request['secret'] === 'secret' && $request['response'] === 'good');
});

it('escapes HTML and turns links into nofollow links', function () {
    $comment = Comment::factory()->create(['commentable_id' => $this->post->id, 'body' => "<script>alert(1)</script>\nBak: https://kadir.guelec.eu."]);

    expect($comment->body_html)
        ->not->toContain('<script>')
        ->toContain('&lt;script&gt;')
        ->toContain('<br>')
        ->toContain('<a href="https://kadir.guelec.eu" rel="nofollow ugc noopener"')
        ->toContain('</a>.');
});

it('shows the comments of a deleted account as "silinmiş üye"', function () {
    $member = User::factory()->member()->create(['name' => 'Gidecek Kişi']);
    Comment::factory()->for($member)->create(['commentable_id' => $this->post->id, 'body' => 'Kalıcı not']);

    $member->delete();

    $this->get(route('posts.show', $this->post->slug))->assertOk()->assertSeeText('silinmiş üye')->assertSeeText('Kalıcı not')->assertDontSeeText('Gidecek Kişi');
});

it('asks guests to sign in', function () {
    $this->get(route('posts.show', $this->post->slug))->assertSeeText('Yorum yazmak için');
});

describe('admin queue', function () {
    beforeEach(fn () => $this->actingAs(User::factory()->admin()->create()));

    it('approves and deletes waiting comments', function () {
        [$first, $second] = Comment::factory()->pending()->count(2)->create(['commentable_id' => $this->post->id])->all();

        Livewire::test('pages::admin.comments.index')->assertSee($first->body)->call('approve', $first->id)->call('delete', $second->id);

        expect($first->fresh()->isApproved())->toBeTrue();
        $this->assertModelMissing($second);
    });

    it('blocks the author of a comment', function () {
        $comment = Comment::factory()->pending()->create(['commentable_id' => $this->post->id]);

        Livewire::test('pages::admin.comments.index')->call('blockAuthor', $comment->id);

        expect($comment->user->fresh()->isBlocked())->toBeTrue();
    });
});
