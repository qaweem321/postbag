<?php

namespace App\Mail;

use App\Models\Subscription;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\URL;

class ConfirmSubscription extends Mailable implements ShouldQueue
{
    use Queueable, SerializesModels;

    public function __construct(public Subscription $subscription)
    {
        $this->subscription->loadMissing('mailingList');
    }

    public function envelope(): Envelope
    {
        return new Envelope(subject: "Please confirm your subscription to {$this->subscription->mailingList->name}");
    }

    public function content(): Content
    {
        return new Content(markdown: 'emails.confirm-subscription', with: [
            'listName' => $this->subscription->mailingList->name,
            // Signed and valid for a week: the link can't be guessed or edited to confirm someone else.
            'url' => URL::temporarySignedRoute('subscriptions.confirm', now()->addDays(7), $this->subscription),
        ]);
    }
}
