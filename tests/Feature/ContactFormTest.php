<?php

use App\Actions\Contact\SendContactMessage;
use App\Mail\ContactMessage;
use App\Models\User;
use Illuminate\Support\Facades\Mail;
use Livewire\Features\SupportTesting\Testable;
use Livewire\Livewire;

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

    Mail::assertSent(ContactMessage::class, fn (ContactMessage $mail): bool => $mail->hasTo('gelen@example.com')
        && $mail->hasReplyTo('ayse@example.com', 'Ayşe Yılmaz')
        && $mail->body === "Merhaba Kadir,\nfilm önerin var mı?");
});

it('falls back to the imprint address without a contact address', function () {
    config(['mail.contact_to' => null, 'legal.email' => 'kunye@example.com']);

    contactForm()->call('send');

    Mail::assertSent(ContactMessage::class, fn (ContactMessage $mail): bool => $mail->hasTo('kunye@example.com'));
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
    $mail = new ContactMessage('Ali', 'ali@example.com', "<script>x</script>\nikinci satır");

    $mail->assertSeeInHtml('&lt;script&gt;x&lt;/script&gt;<br />', false)
        ->assertDontSeeInHtml('<script>', false)
        ->assertSeeInText('<script>x</script>');
});
