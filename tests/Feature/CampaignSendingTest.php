<?php

use App\Enums\CampaignStatus;
use App\Enums\SendStatus;
use App\Enums\SubscriberStatus;
use App\Enums\SubscriptionStatus;
use App\Jobs\SendCampaignBatch;
use App\Mail\CampaignMail;
use App\Models\Campaign;
use App\Models\CampaignSend;
use App\Models\MailingList;
use App\Models\Subscription;
use App\Services\CampaignRenderer;
use App\Services\CampaignSender;
use App\Services\CampaignStats;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\URL;

beforeEach(function () {
    Mail::fake();
    config(['postbag.batch_size' => 2]);
    $this->list = MailingList::factory()->create(['name' => 'Weekly digest']);
    $this->campaign = Campaign::factory()->for($this->list)->create();
});

function subscribers(MailingList $list, int $count, array $state = []): void
{
    Subscription::factory()->count($count)->for($list)->create($state);
}

it('sends to every subscribed, non-suppressed member exactly once', function () {
    subscribers($this->list, 5);
    subscribers($this->list, 2, ['status' => SubscriptionStatus::Unsubscribed]);
    subscribers($this->list, 1, ['status' => SubscriptionStatus::Pending]);
    Subscription::factory()->for($this->list)->create()->subscriber->update(['status' => SubscriberStatus::Bounced]);

    expect(app(CampaignSender::class)->start($this->campaign))->toBeTrue();

    Mail::assertSentCount(5);
    expect($this->campaign->fresh())
        ->status->toBe(CampaignStatus::Sent)
        ->recipients_count->toBe(5)
        ->finished_at->not->toBeNull();
    expect(CampaignSend::query()->where('status', SendStatus::Sent)->count())->toBe(5);
});

it('cannot be started twice', function () {
    subscribers($this->list, 3);
    $sender = app(CampaignSender::class);

    expect($sender->start($this->campaign))->toBeTrue()
        ->and($sender->start($this->campaign->fresh()))->toBeFalse();
    Mail::assertSentCount(3);
});

it('never emails a recipient twice when a batch job runs again', function () {
    subscribers($this->list, 2);
    app(CampaignSender::class)->start($this->campaign);
    $ids = CampaignSend::query()->pluck('id')->all();

    // A retried or duplicated job finds every send already claimed.
    (new SendCampaignBatch($this->campaign->id, $ids))->handle(app(CampaignRenderer::class));

    Mail::assertSentCount(2);
});

it('skips people who unsubscribed after the campaign started', function () {
    subscribers($this->list, 2);
    $this->campaign->forceFill(['status' => CampaignStatus::Sending])->save();
    DB::insert(
        'INSERT INTO campaign_sends (uuid, campaign_id, subscriber_id, subscription_id, status, created_at, updated_at)
         SELECT UUID(), ?, subscriber_id, id, ?, NOW(), NOW() FROM subscriptions', [$this->campaign->id, 'pending']);
    Subscription::query()->first()->update(['status' => SubscriptionStatus::Unsubscribed]);

    (new SendCampaignBatch($this->campaign->id, CampaignSend::query()->pluck('id')->all()))->handle(app(CampaignRenderer::class));

    Mail::assertSentCount(1);
    expect(CampaignSend::query()->where('status', SendStatus::Skipped)->count())->toBe(1);
});

it('personalizes, tracks links and adds one-click unsubscribe headers', function () {
    $subscription = Subscription::factory()->for($this->list)->create();
    $subscription->subscriber->update(['name' => '<b>Jane</b>']);

    app(CampaignSender::class)->start($this->campaign);

    Mail::assertSent(CampaignMail::class, function (CampaignMail $mail) use ($subscription) {
        $headers = $mail->headers()->text;

        return str_contains($mail->htmlBody, 'Hi &lt;b&gt;Jane&lt;/b&gt;,')
            && str_contains($mail->htmlBody, '/t/c/')
            && ! str_contains($mail->htmlBody, 'href="https://example.com/changelog"')
            && str_contains($mail->htmlBody, '/t/o/')
            && str_contains($mail->textBody, 'https://example.com/changelog')
            && str_contains($headers['List-Unsubscribe'], "/unsubscribe/{$subscription->uuid}?signature=")
            && $headers['List-Unsubscribe-Post'] === 'List-Unsubscribe=One-Click';
    });
    expect($this->campaign->links()->pluck('url')->all())
        ->toBe(['https://example.com/changelog', 'https://example.com/docs']);
});

it('counts opens and clicks, and only redirects to the campaign\'s own links', function () {
    subscribers($this->list, 1);
    app(CampaignSender::class)->start($this->campaign);
    $send = CampaignSend::query()->sole();
    $link = $this->campaign->links()->first();

    $this->get(URL::signedRoute('track.open', ['send' => $send->uuid]))->assertOk()->assertHeader('Content-Type', 'image/gif');
    $this->get(URL::signedRoute('track.open', ['send' => $send->uuid]))->assertOk();
    $this->get(URL::signedRoute('track.click', ['send' => $send->uuid, 'link' => $link->id]))->assertRedirect($link->url);

    $other = Campaign::factory()->for($this->list)->create();
    $foreign = $other->links()->create(['url' => 'https://evil.example', 'url_hash' => hash('sha256', 'https://evil.example')]);
    $this->get(URL::signedRoute('track.click', ['send' => $send->uuid, 'link' => $foreign->id]))->assertNotFound();
    $this->get(route('track.open', ['send' => $send->uuid]))->assertForbidden();

    expect($send->fresh())->open_count->toBe(2)->click_count->toBe(1)->opened_at->not->toBeNull();
    $stats = app(CampaignStats::class)->for($this->campaign);
    expect($stats)->toMatchArray(['recipients' => 1, 'sent' => 1, 'opened' => 1, 'clicked' => 1, 'open_rate' => 1.0])
        ->and($stats['links'][0])->toBe(['url' => $link->url, 'clicks' => 1]);
});

it('marks failed deliveries without stopping the rest', function () {
    subscribers($this->list, 2);
    Mail::shouldReceive('to')->twice()->andReturnUsing(function () {
        static $call = 0;
        $call++;
        $pending = Mockery::mock();
        $pending->shouldReceive('send')->andReturnUsing(fn () => $call === 1 ? throw new RuntimeException('SMTP said no') : null);

        return $pending;
    });

    app(CampaignSender::class)->start($this->campaign);

    expect(CampaignSend::query()->pluck('status')->map->value->sort()->values()->all())->toBe(['failed', 'sent'])
        ->and(CampaignSend::query()->where('status', SendStatus::Failed)->value('error'))->toBe('SMTP said no');
});

it('starts scheduled campaigns when their time comes', function () {
    subscribers($this->list, 1);
    $this->campaign->forceFill(['status' => CampaignStatus::Scheduled, 'scheduled_at' => now()->addMinutes(5)])->save();

    $this->artisan('postbag:send-scheduled')->expectsOutput('Started 0 campaign(s).');
    $this->travel(6)->minutes();
    $this->artisan('postbag:send-scheduled')->expectsOutput('Started 1 campaign(s).');

    Mail::assertSentCount(1);
});

it('cancels a sending campaign and skips the rest', function () {
    $this->campaign->forceFill(['status' => CampaignStatus::Sending])->save();
    subscribers($this->list, 2);
    DB::insert(
        'INSERT INTO campaign_sends (uuid, campaign_id, subscriber_id, subscription_id, status, created_at, updated_at)
         SELECT UUID(), ?, subscriber_id, id, ?, NOW(), NOW() FROM subscriptions', [$this->campaign->id, 'pending']);

    app(CampaignSender::class)->cancel($this->campaign);

    expect($this->campaign->fresh()->status)->toBe(CampaignStatus::Cancelled)
        ->and(CampaignSend::query()->where('status', SendStatus::Skipped)->count())->toBe(2);
});
