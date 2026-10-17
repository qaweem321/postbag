<?php

namespace App\Services;

use App\Enums\SubscribeOutcome;
use App\Enums\SubscriberStatus;
use App\Enums\SubscriptionStatus;
use App\Exceptions\SuppressedAddress;
use App\Mail\ConfirmSubscription;
use App\Models\MailingList;
use App\Models\Subscriber;
use App\Models\Subscription;
use Illuminate\Support\Facades\Mail;

class SubscriptionManager
{
    /** How long before a pending subscriber can be sent another confirmation email. */
    public const CONFIRMATION_RESEND_MINUTES = 15;

    /**
     * Adds an email address to a list. With double opt-in the address stays pending until the
     * confirmation link is clicked. Safe to call again for the same address: an existing
     * subscription is reused, never duplicated.
     */
    public function subscribe(
        MailingList $list,
        string $email,
        ?string $name = null,
        string $source = 'api',
        bool $skipConfirmation = false,
    ): SubscribeOutcome {
        // createOrFirst lets the unique index settle races between two requests for the same address.
        $subscriber = Subscriber::query()->createOrFirst(
            ['email' => Subscriber::normalizeEmail($email)],
            ['name' => $name],
        );
        if ($subscriber->status->isSuppressed()) {
            throw new SuppressedAddress($subscriber->email);
        }
        if ($name !== null && $name !== '' && $subscriber->name === null) {
            $subscriber->update(['name' => $name]);
        }

        $subscription = Subscription::query()->createOrFirst(
            ['mailing_list_id' => $list->id, 'subscriber_id' => $subscriber->id],
            ['status' => SubscriptionStatus::Pending, 'source' => $source],
        );
        if ($subscription->status === SubscriptionStatus::Subscribed) {
            return SubscribeOutcome::AlreadySubscribed;
        }

        if (! $list->double_opt_in || $skipConfirmation) {
            $subscription->update([
                'status' => SubscriptionStatus::Subscribed,
                'confirmed_at' => now(),
                'unsubscribed_at' => null,
            ]);

            return SubscribeOutcome::Subscribed;
        }

        $subscription->status = SubscriptionStatus::Pending;
        $recentlySent = $subscription->confirmation_sent_at?->gt(now()->subMinutes(self::CONFIRMATION_RESEND_MINUTES));
        if (! $recentlySent) {
            $subscription->confirmation_sent_at = now();
            Mail::to($subscriber->email)->queue(new ConfirmSubscription($subscription));
        }
        $subscription->save();

        return SubscribeOutcome::PendingConfirmation;
    }

    public function confirm(Subscription $subscription): void
    {
        if ($subscription->status === SubscriptionStatus::Pending) {
            $subscription->update(['status' => SubscriptionStatus::Subscribed, 'confirmed_at' => now()]);
        }
    }

    public function unsubscribe(Subscription $subscription): void
    {
        if ($subscription->status !== SubscriptionStatus::Unsubscribed) {
            $subscription->update(['status' => SubscriptionStatus::Unsubscribed, 'unsubscribed_at' => now()]);
        }
    }

    /** Records a hard bounce or spam complaint: the address gets no more email from any list. */
    public function suppress(string $email, SubscriberStatus $reason): Subscriber
    {
        $subscriber = Subscriber::query()->createOrFirst(['email' => Subscriber::normalizeEmail($email)]);
        if (! $subscriber->status->isSuppressed()) {
            $subscriber->update(['status' => $reason, 'suppressed_at' => now()]);
        }

        return $subscriber;
    }
}
