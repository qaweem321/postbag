<?php

use App\Enums\SubscriberStatus;
use App\Models\Campaign;
use App\Models\MailingList;
use App\Models\Subscriber;
use App\Models\Subscription;
use Illuminate\Support\Facades\Mail;

beforeEach(fn () => Mail::fake());

it('requires a token, and the right ability', function () {
    $list = MailingList::factory()->create();

    $this->getJson('/api/v1/lists')->assertUnauthorized();
    $this->postJson("/api/v1/lists/{$list->uuid}/subscribers", ['email' => 'a@example.com'], apiToken(['read']))->assertForbidden();
    // Tests share one app instance: forget the user authenticated by the previous request.
    app('auth')->forgetGuards();
    $this->getJson('/api/v1/lists', apiToken(['write']))->assertForbidden();
});

it('lists mailing lists with their subscriber counts', function () {
    $list = MailingList::factory()->create(['name' => 'Weekly']);
    Subscription::factory()->count(3)->for($list)->create();

    $this->getJson('/api/v1/lists', apiToken())
        ->assertOk()
        ->assertJsonPath('data.0.id', $list->uuid)
        ->assertJsonPath('data.0.subscribed_count', 3)
        ->assertJsonMissingPath('data.0.uuid');
});

it('subscribes through the API and is idempotent', function () {
    $list = MailingList::factory()->singleOptIn()->create();
    $headers = apiToken();

    $this->postJson("/api/v1/lists/{$list->uuid}/subscribers", ['email' => 'Jane@Example.com', 'name' => 'Jane', 'source' => 'wordpress'], $headers)
        ->assertCreated()
        ->assertJsonPath('outcome', 'subscribed')
        ->assertJsonPath('data.email', 'jane@example.com')
        ->assertJsonPath('data.source', 'wordpress');

    $this->postJson("/api/v1/lists/{$list->uuid}/subscribers", ['email' => 'jane@example.com'], $headers)
        ->assertOk()
        ->assertJsonPath('outcome', 'already_subscribed');
});

it('validates input and reports suppressed addresses', function () {
    $list = MailingList::factory()->create();
    $headers = apiToken();

    $this->postJson("/api/v1/lists/{$list->uuid}/subscribers", ['email' => 'not-an-email'], $headers)
        ->assertUnprocessable()->assertJsonValidationErrors('email');

    $this->postJson('/api/v1/suppressions', ['email' => 'gone@example.com', 'reason' => 'bounced'], $headers)->assertOk();
    $this->postJson("/api/v1/lists/{$list->uuid}/subscribers", ['email' => 'gone@example.com'], $headers)
        ->assertUnprocessable()->assertJsonPath('message', fn ($m) => str_contains($m, 'can\'t receive email'));
    expect(Subscriber::query()->where('email', 'gone@example.com')->value('status'))->toBe(SubscriberStatus::Bounced);
});

it('pages subscribers with a cursor and filters by status', function () {
    $list = MailingList::factory()->create();
    Subscription::factory()->count(3)->for($list)->create();
    Subscription::factory()->for($list)->create(['status' => 'unsubscribed']);
    $headers = apiToken();

    $first = $this->getJson("/api/v1/lists/{$list->uuid}/subscribers?per_page=2&status=subscribed", $headers)->assertOk();
    expect($first->json('data'))->toHaveCount(2);
    $second = $this->getJson("/api/v1/lists/{$list->uuid}/subscribers?per_page=2&status=subscribed&cursor=".$first->json('meta.next_cursor'), $headers);
    expect($second->json('data'))->toHaveCount(1)->and($second->json('meta.next_cursor'))->toBeNull();
});

it('unsubscribes and shows a subscriber by email', function () {
    $subscription = Subscription::factory()->create();
    $list = $subscription->mailingList;
    $email = $subscription->subscriber->email;

    $this->getJson("/api/v1/lists/{$list->uuid}/subscribers/".strtoupper($email), apiToken())->assertOk()->assertJsonPath('data.status', 'subscribed');
    $this->deleteJson("/api/v1/lists/{$list->uuid}/subscribers/{$email}", [], apiToken())->assertOk()->assertJsonPath('data.status', 'unsubscribed');
    $this->getJson("/api/v1/lists/{$list->uuid}/subscribers/nobody@example.com", apiToken())->assertNotFound();
});

it('shows campaigns with statistics', function () {
    $campaign = Campaign::factory()->create();

    $this->getJson("/api/v1/campaigns/{$campaign->uuid}", apiToken())
        ->assertOk()
        ->assertJsonPath('data.status', 'draft')
        ->assertJsonPath('stats.recipients', 0);
});

it('rate-limits each token', function () {
    $headers = apiToken();
    for ($i = 0; $i < 120; $i++) {
        $this->getJson('/api/v1/lists', $headers)->assertOk();
    }
    $this->getJson('/api/v1/lists', $headers)->assertTooManyRequests();
});
