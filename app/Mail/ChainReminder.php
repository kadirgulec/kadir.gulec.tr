<?php

namespace App\Mail;

use App\Models\Goal;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

/**
 * The chains that need Kadir today ("Spor: bu hafta 0/2, 4 günde 2 kez daha"),
 * sent to him alone by chains:remind. Hidden chains are included: it is his own list.
 */
class ChainReminder extends Mailable
{
    use Queueable, SerializesModels;

    /**
     * @param  list<array{chain: Goal, key: string, text: string}>  $entries
     */
    public function __construct(public array $entries) {}

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: count($this->entries) === 1
                ? $this->entries[0]['chain']->title.': '.$this->entries[0]['text']
                : count($this->entries).' zincir seni bekliyor',
        );
    }

    public function content(): Content
    {
        return new Content(
            view: 'mail.chain-reminder',
            text: 'mail.chain-reminder-text',
            with: [
                'lines' => array_map(fn (array $entry): array => [
                    'title' => $entry['chain']->title,
                    'cadence' => $entry['chain']->chain_period->cadence($entry['chain']->chain_target),
                    'text' => $entry['text'],
                ], $this->entries),
                'dashboardUrl' => route('admin.dashboard'),
            ],
        );
    }
}
