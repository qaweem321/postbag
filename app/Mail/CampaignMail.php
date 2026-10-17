<?php

namespace App\Mail;

use App\Models\Campaign;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Address;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Mail\Mailables\Headers;

/** One campaign email, already rendered for its recipient. Sent synchronously by the queued batch job. */
class CampaignMail extends Mailable
{
    public function __construct(
        public Campaign $campaign,
        public string $htmlBody,
        public string $textBody,
        public string $unsubscribeUrl,
    ) {}

    public function envelope(): Envelope
    {
        return new Envelope(
            from: new Address($this->campaign->from_email, $this->campaign->from_name),
            subject: $this->campaign->subject,
        );
    }

    /** RFC 8058 one-click unsubscribe: Gmail and Yahoo require it for bulk senders. */
    public function headers(): Headers
    {
        return new Headers(text: [
            'List-Unsubscribe' => "<{$this->unsubscribeUrl}>",
            'List-Unsubscribe-Post' => 'List-Unsubscribe=One-Click',
            'X-Postbag-Campaign' => $this->campaign->uuid,
        ]);
    }

    public function content(): Content
    {
        return new Content(htmlString: $this->htmlBody, text: 'emails.text', with: ['text' => $this->textBody]);
    }
}
