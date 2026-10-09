<?php

namespace App\Mail;

use App\Models\MonthlyReview;
use App\Support\Reviews\ReviewSuggestions;
use App\Support\TurkishDate;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

/**
 * "Ekim değerlendirmesi hazır": the draft of last month's review was made by
 * reviews:create. Sent to Kadir alone, with the suggested lines as a nudge.
 */
class MonthlyReviewReady extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(public MonthlyReview $review) {}

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: TurkishDate::month($this->review->month).' değerlendirmesi hazır',
        );
    }

    public function content(): Content
    {
        return new Content(
            view: 'mail.monthly-review-ready',
            text: 'mail.monthly-review-ready-text',
            with: [
                'monthName' => TurkishDate::monthYear($this->review->month),
                'suggestions' => array_map(fn (array $suggestion): array => [
                    'sign' => $suggestion['kind']->sign(),
                    'text' => $suggestion['text'],
                ], app(ReviewSuggestions::class)->for($this->review)),
                'editUrl' => route('admin.reviews.edit', $this->review),
            ],
        );
    }
}
