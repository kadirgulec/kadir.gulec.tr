<?php

use App\Actions\Contact\SendContactMessage;
use App\Mail\ContactMessageReceived;
use App\Models\ContactMessage;
use App\Models\User;
use Illuminate\Support\Facades\Mail;
use Livewire\Features\SupportTesting\Testable;
use Livewire\Livewire;
use Symfony\Component\Mailer\Exception\TransportException;

beforeEach(function () {
    Mail::fake();
});

function contactForm(): Testable
{
    return Livewire::test('site.contact-form')
        ->set('name', 'Ayşe Yılmaz')
        ->set('email', 'ayse@example.com')
        ->set('message', "Merhaba Kadir,\nfilm önerin var mı?");
}

it('shows the form on the about page instead of the e-mail address', function () {
    $this->get(route('about'))
        ->assertSeeLivewire('site.contact-form')
        ->assertDontSee('mailto:', false);
});

it('mails the message to the address from the env with the sender as reply-to', function () {
    config(['mail.contact_to' => 'gelen@example.com']);

    contactForm()->call('send')
        ->assertHasNoErrors()
        ->assertSet('message', '')
        ->assertSee('Mesajın ulaştı');

    Mail::assertSent(ContactMessageReceived::class, fn (ContactMessageReceived $mail): bool => $mail->hasTo('gelen@example.com')
        && $mail->hasReplyTo('ayse@example.com', 'Ayşe Yılmaz')
        && $mail->contactMessage->is(ContactMessage::sole()));
});

it('saves the message for the admin inbox, without IP or account', function () {
    $this->actingAs(User::factory()->member()->create());

    contactForm()->call('send');

    $message = ContactMessage::sole();
    expect($message->only(['name', 'email', 'body']))->toBe(['name' => 'Ayşe Yılmaz', 'email' => 'ayse@example.com', 'body' => "Merhaba Kadir,\nfilm önerin var mı?"])
        ->and($message->isRead())->toBeFalse()
        ->and(array_keys($message->getAttributes()))->toEqualCanonicalizing(['id', 'name', 'email', 'body', 'read_at', 'created_at', 'updated_at']);
});

it('keeps the message when the mail server fails', function () {
    Mail::shouldReceive('to->send')->andThrow(new TransportException('bağlantı yok'));

    contactForm()->call('send')->assertHasNoErrors()->assertSee('Mesajın ulaştı');

    expect(ContactMessage::query()->count())->toBe(1);
});

it('prunes messages older than a year', function () {
    $old = ContactMessage::factory()->create(['created_at' => now()->subMonths(ContactMessage::KEEP_MONTHS)->subDay()]);
    $recent = ContactMessage::factory()->create(['created_at' => now()->subMonths(ContactMessage::KEEP_MONTHS)->addDay()]);

    $this->artisan('model:prune', ['--model' => [ContactMessage::class]])->assertSuccessful();

    expect(ContactMessage::query()->pluck('id')->all())->toBe([$recent->id])
        ->and($old->fresh())->toBeNull();
});

it('falls back to the imprint address without a contact address', function () {
    config(['mail.contact_to' => null, 'legal.email' => 'kunye@example.com']);

    contactForm()->call('send');

    Mail::assertSent(ContactMessageReceived::class, fn (ContactMessageReceived $mail): bool => $mail->hasTo('kunye@example.com'));
});

it('fills in the name and e-mail of a signed-in member', function () {
    $this->actingAs(User::factory()->member()->create(['name' => 'Mehmet', 'email' => 'mehmet@example.com']));

    Livewire::test('site.contact-form')
        ->assertSet('name', 'Mehmet')
        ->assertSet('email', 'mehmet@example.com');
});

it('validates the fields', function () {
    Livewire::test('site.contact-form')
        ->set('email', 'e-posta değil')
        ->set('message', 'kısa')
        ->call('send')
        ->assertHasErrors(['name' => 'required', 'email' => 'email', 'message' => 'min']);

    Mail::assertNothingSent();
    expect(ContactMessage::query()->count())->toBe(0);
});

it('rejects bots that fill in the honeypot', function () {
    contactForm()->set('website', 'https://spam.example')->call('send')->assertHasErrors('website');

    Mail::assertNothingSent();
});

it('checks the Turnstile token when a secret is configured', function () {
    config(['services.turnstile.secret_key' => 'gizli']);

    contactForm()->call('send')->assertHasErrors('turnstileToken');

    Mail::assertNothingSent();
});

it('limits how many messages one sender can send', function () {
    foreach (range(1, SendContactMessage::PER_HOUR) as $attempt) {
        contactForm()->call('send')->assertHasNoErrors();
    }

    contactForm()->call('send')->assertHasErrors('message');

    Mail::assertSentCount(SendContactMessage::PER_HOUR);
});

it('renders the e-mail with the message escaped', function () {
    $mail = new ContactMessageReceived(ContactMessage::factory()->make(['name' => 'Ali', 'body' => "<script>x</script>\nikinci satır"]));

    $mail->assertSeeInHtml('&lt;script&gt;x&lt;/script&gt;<br />', false)
        ->assertDontSeeInHtml('<script>', false)
        ->assertSeeInText('<script>x</script>')
        ->assertSeeInHtml(route('admin.messages.index'));
});
