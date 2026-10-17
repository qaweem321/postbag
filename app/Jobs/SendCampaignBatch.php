<?php

namespace App\Jobs;

use App\Enums\SendStatus;
use App\Enums\SubscriptionStatus;
use App\Mail\CampaignMail;
use App\Models\Campaign;
use App\Models\CampaignSend;
use App\Services\CampaignRenderer;
use Illuminate\Bus\Batchable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Queue\Middleware\RateLimited;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Str;
use Throwable;

class SendCampaignBatch implements ShouldQueue
{
    use Batchable, Queueable;

    /**
     * Rate limiting releases the job back to the queue, which counts as an attempt, so retries
     * are bounded by time instead, and by real errors (maxExceptions).
     */
    public int $maxExceptions = 3;

    public function retryUntil(): \DateTimeInterface
    {
        return now()->addDay();
    }

    /** @param  list<int>  $sendIds */
    public function __construct(public int $campaignId, public array $sendIds) {}

    /** @return list<object> */
    public function middleware(): array
    {
        return [new RateLimited('campaign-emails')];
    }

    public function handle(CampaignRenderer $renderer): void
    {
        if ($this->batch()?->cancelled()) {
            return;
        }
        $campaign = Campaign::query()->with(['mailingList', 'links'])->find($this->campaignId);
        if ($campaign === null) {
            return;
        }
        $linkIds = $campaign->links->pluck('id', 'url')->all();

        $sends = CampaignSend::query()
            ->with(['subscriber', 'subscription'])
            ->whereIn('id', $this->sendIds)
            ->where('status', SendStatus::Pending)
            ->get();

        foreach ($sends as $send) {
            // Claim the send. Only the job that flips pending -> sending sends it, so a retried
            // or duplicated job can never email the same person twice.
            $claimed = CampaignSend::query()->whereKey($send->id)->where('status', SendStatus::Pending)
                ->update(['status' => SendStatus::Sending, 'updated_at' => now()]);
            if ($claimed !== 1) {
                continue;
            }

            // Unsubscribed or bounced since the campaign started.
            if ($send->subscription->status !== SubscriptionStatus::Subscribed || $send->subscriber->status->isSuppressed()) {
                $send->forceFill(['status' => SendStatus::Skipped])->save();

                continue;
            }

            try {
                $email = $renderer->render($campaign, $send, $send->subscriber, $linkIds);
                Mail::to($send->subscriber->email, $send->subscriber->name)
                    ->send(new CampaignMail($campaign, $email['html'], $email['text'], $email['unsubscribe_url']));
                $send->forceFill(['status' => SendStatus::Sent, 'sent_at' => now(), 'error' => null])->save();
            } catch (Throwable $error) {
                $send->forceFill(['status' => SendStatus::Failed, 'error' => Str::limit($error->getMessage(), 250)])->save();
            }
        }
    }
}
