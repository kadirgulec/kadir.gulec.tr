<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Address;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;

/**
 * A message from the contact form. Replying in the mail program answers the sender.
 */
class ContactMessage extends Mailable
{
    use Queueable;

    public function __construct(public string $senderName, public string $senderEmail, public string $body) {}

    public function envelope(): Envelope
    {
        return new Envelope(
            replyTo: [new Address($this->senderEmail, $this->senderName)],
            subject: 'İletişim formu: '.$this->senderName,
        );
    }

    public function content(): Content
    {
        return new Content(
            view: 'mail.contact-message',
            text: 'mail.contact-message-text',
        );
    }
}
