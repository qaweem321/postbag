<?php

use App\Enums\SubscribeOutcome;
use App\Enums\SubscriberStatus;
use App\Enums\SubscriptionStatus;
use App\Exceptions\SuppressedAddress;
use App\Mail\ConfirmSubscription;
use App\Models\MailingList;
use App\Models\Subscription;
use App\Services\SubscriptionManager;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\URL;

beforeEach(fn () => Mail::fake());

it('keeps double opt-in subscribers pending and emails a confirmation link', function () {
    $list = MailingList::factory()->create();

    $outcome = app(SubscriptionManager::class)->subscribe($list, '  Jane@Example.COM ', 'Jane');

    expect($outcome)->toBe(SubscribeOutcome::PendingConfirmation);
    $subscription = Subscription::query()->with('subscriber')->sole();
    expect($subscription->status)->toBe(SubscriptionStatus::Pending)
        ->and($subscription->subscriber->email)->toBe('jane@example.com');
    Mail::assertQueued(ConfirmSubscription::class, fn ($mail) => $mail->hasTo('jane@example.com'));
});

it('subscribes immediately on single opt-in lists', function () {
    $list = MailingList::factory()->singleOptIn()->create();

    expect(app(SubscriptionManager::class)->subscribe($list, 'a@example.com'))->toBe(SubscribeOutcome::Subscribed);
    expect(app(SubscriptionManager::class)->subscribe($list, 'A@example.com'))->toBe(SubscribeOutcome::AlreadySubscribed);
    expect(Subscription::query()->count())->toBe(1);
    Mail::assertNothingQueued();
});

it('throttles repeated confirmation emails so a form cannot flood an inbox', function () {
    $list = MailingList::factory()->create();
    $manager = app(SubscriptionManager::class);

    $manager->subscribe($list, 'a@example.com');
    $manager->subscribe($list, 'a@example.com');
    Mail::assertQueuedCount(1);

    $this->travel(SubscriptionManager::CONFIRMATION_RESEND_MINUTES + 1)->minutes();
    $manager->subscribe($list, 'a@example.com');
    Mail::assertQueuedCount(2);
});

it('refuses suppressed addresses on every list', function () {
    $list = MailingList::factory()->singleOptIn()->create();
    $manager = app(SubscriptionManager::class);
    $manager->suppress('bounced@example.com', SubscriberStatus::Bounced);

    $manager->subscribe($list, 'Bounced@example.com');
})->throws(SuppressedAddress::class);

it('confirms only through a POST to the signed link', function () {
    $list = MailingList::factory()->create(['name' => 'Weekly digest']);
    app(SubscriptionManager::class)->subscribe($list, 'a@example.com');
    $subscription = Subscription::query()->sole();
    $url = URL::temporarySignedRoute('subscriptions.confirm', now()->addDay(), $subscription);

    // Opening the link (or a mail scanner opening it) changes nothing.
    $this->get($url)->assertOk()->assertSee('Yes, subscribe me');
    expect($subscription->fresh()->status)->toBe(SubscriptionStatus::Pending);

    $this->post($url)->assertOk()->assertSee('You\'re subscribed');
    expect($subscription->fresh()->status)->toBe(SubscriptionStatus::Subscribed);

    $this->post(route('subscriptions.confirm', $subscription))->assertForbidden();
    $this->travel(2)->days();
    $this->post($url)->assertForbidden();
});

it('unsubscribes with one click, without a CSRF token', function () {
    $subscription = Subscription::factory()->create();
    $url = URL::signedRoute('subscriptions.unsubscribe', $subscription);

    $this->get($url)->assertOk()->assertSee('Unsubscribe');
    $this->post($url, ['List-Unsubscribe' => 'One-Click'])->assertNoContent();

    expect($subscription->fresh())
        ->status->toBe(SubscriptionStatus::Unsubscribed)
        ->unsubscribed_at->not->toBeNull();
});
