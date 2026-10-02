<?php

namespace App\Mail;

use App\Models\ContactMessage;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Address;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

/**
 * A message from the contact form, sent to Kadir. Replying in the mail
 * program answers the sender; the message is also in the admin inbox.
 */
class ContactMessageReceived extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(public ContactMessage $contactMessage) {}

    public function envelope(): Envelope
    {
        return new Envelope(
            replyTo: [new Address($this->contactMessage->email, $this->contactMessage->name)],
            subject: 'İletişim formu: '.$this->contactMessage->name,
        );
    }

    public function content(): Content
    {
        return new Content(
            view: 'mail.contact-message',
            text: 'mail.contact-message-text',
            with: [
                'senderName' => $this->contactMessage->name,
                'senderEmail' => $this->contactMessage->email,
                'body' => $this->contactMessage->body,
                'inboxUrl' => route('admin.messages.index'),
            ],
        );
    }
}
