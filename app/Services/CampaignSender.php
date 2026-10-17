<?php

namespace App\Services;

use App\Enums\CampaignStatus;
use App\Enums\SendStatus;
use App\Enums\SubscriberStatus;
use App\Enums\SubscriptionStatus;
use App\Jobs\SendCampaignBatch;
use App\Models\Campaign;
use App\Models\CampaignLink;
use Illuminate\Bus\Batch;
use Illuminate\Support\Facades\Bus;
use Illuminate\Support\Facades\DB;

class CampaignSender
{
    public function __construct(private CampaignRenderer $renderer) {}

    /**
     * Starts sending: freezes the recipient list and queues the emails in batches. Returns false
     * if the campaign was already started (by a second click, or the scheduler at the same moment).
     */
    public function start(Campaign $campaign): bool
    {
        $started = DB::transaction(function () use ($campaign) {
            // The row lock makes the status check and change one step: only one caller gets past it.
            $locked = Campaign::query()->lockForUpdate()->find($campaign->id);
            if ($locked === null || ! $locked->status->isEditable()) {
                return false;
            }

            // One INSERT ... SELECT copies every current subscriber into the send list, however
            // large the list. The unique (campaign_id, subscriber_id) key means a recipient can
            // only be queued once; INSERT IGNORE makes running it twice harmless.
            $recipients = DB::affectingStatement(
                'INSERT IGNORE INTO campaign_sends (uuid, campaign_id, subscriber_id, subscription_id, status, created_at, updated_at)
                 SELECT UUID(), ?, sub.subscriber_id, sub.id, ?, NOW(), NOW()
                 FROM subscriptions sub
                 JOIN subscribers s ON s.id = sub.subscriber_id
                 WHERE sub.mailing_list_id = ? AND sub.status = ? AND s.status = ?',
                [$locked->id, SendStatus::Pending->value, $locked->mailing_list_id,
                    SubscriptionStatus::Subscribed->value, SubscriberStatus::Active->value],
            );

            foreach ($this->renderer->links($locked) as $url) {
                CampaignLink::query()->firstOrCreate(['campaign_id' => $locked->id, 'url_hash' => hash('sha256', $url)], ['url' => $url]);
            }

            $locked->forceFill([
                'status' => CampaignStatus::Sending,
                'started_at' => now(),
                'recipients_count' => $recipients,
            ])->save();

            return true;
        });

        if (! $started) {
            return false;
        }

        $this->queueBatches($campaign->refresh());

        return true;
    }

    /** Stops a sending campaign: queued jobs are cancelled and unsent emails are marked skipped. */
    public function cancel(Campaign $campaign): void
    {
        if ($campaign->batch_id !== null) {
            Bus::findBatch($campaign->batch_id)?->cancel();
        }
        $campaign->sends()->where('status', SendStatus::Pending)->update(['status' => SendStatus::Skipped]);
        $campaign->forceFill(['status' => CampaignStatus::Cancelled, 'finished_at' => now()])->save();
    }

    private function queueBatches(Campaign $campaign): void
    {
        if ($campaign->recipients_count === 0) {
            $campaign->forceFill(['status' => CampaignStatus::Sent, 'finished_at' => now()])->save();

            return;
        }

        $campaignId = $campaign->id;
        $batch = Bus::batch([])
            ->name("campaign:{$campaign->uuid}")
            ->allowFailures()
            // Runs once every job has finished or failed for good.
            ->finally(function (Batch $batch) use ($campaignId) {
                Campaign::query()->whereKey($campaignId)->where('status', CampaignStatus::Sending)
                    ->update(['status' => CampaignStatus::Sent, 'finished_at' => now()]);
            })
            ->dispatch();
        $campaign->forceFill(['batch_id' => $batch->id])->save();

        // Ids are read in pages and added to the batch as jobs, so memory stays flat for big lists.
        $size = max(1, (int) config('postbag.batch_size'));
        $campaign->sends()->select('id')->orderBy('id')->chunkById(1000, function ($sends) use ($batch, $campaignId, $size) {
            $batch->add($sends->pluck('id')->chunk($size)->map(
                fn ($ids) => new SendCampaignBatch($campaignId, array_values(array_map(intval(...), $ids->all()))),
            )->all());
        });
    }
}
