<?php

namespace App\Services;

use App\Enums\SendStatus;
use App\Models\Campaign;
use Illuminate\Support\Facades\DB;

class CampaignStats
{
    /**
     * Delivery and engagement numbers for one campaign, from a single aggregate query.
     *
     * @return array{recipients: int, sent: int, failed: int, skipped: int, queued: int, opened: int, clicked: int, unsubscribed: int, open_rate: float|null, click_rate: float|null, links: array<int, array{url: string, clicks: int}>}
     */
    public function for(Campaign $campaign): array
    {
        $row = DB::table('campaign_sends as cs')
            ->join('subscriptions as sub', 'sub.id', '=', 'cs.subscription_id')
            ->where('cs.campaign_id', $campaign->id)
            ->selectRaw('COUNT(*) AS recipients')
            ->selectRaw('SUM(cs.status = ?) AS sent', [SendStatus::Sent->value])
            ->selectRaw('SUM(cs.status = ?) AS failed', [SendStatus::Failed->value])
            ->selectRaw('SUM(cs.status = ?) AS skipped', [SendStatus::Skipped->value])
            ->selectRaw('SUM(cs.status IN (?, ?)) AS queued', [SendStatus::Pending->value, SendStatus::Sending->value])
            ->selectRaw('SUM(cs.opened_at IS NOT NULL) AS opened')
            ->selectRaw('SUM(cs.clicked_at IS NOT NULL) AS clicked')
            // Recipients who unsubscribed after this campaign went out.
            ->selectRaw('SUM(sub.unsubscribed_at IS NOT NULL AND sub.unsubscribed_at >= cs.sent_at) AS unsubscribed')
            ->first();

        $sent = (int) ($row->sent ?? 0);
        $links = $campaign->links()->orderByDesc('click_count')->limit(10)->get(['url', 'click_count'])
            ->map(fn ($link) => ['url' => $link->url, 'clicks' => $link->click_count])->values()->all();
        $opened = (int) ($row->opened ?? 0);
        $clicked = (int) ($row->clicked ?? 0);

        return [
            'recipients' => (int) ($row->recipients ?? 0),
            'sent' => $sent,
            'failed' => (int) ($row->failed ?? 0),
            'skipped' => (int) ($row->skipped ?? 0),
            'queued' => (int) ($row->queued ?? 0),
            'opened' => $opened,
            'clicked' => $clicked,
            'unsubscribed' => (int) ($row->unsubscribed ?? 0),
            'open_rate' => $sent > 0 ? round($opened / $sent, 4) : null,
            'click_rate' => $sent > 0 ? round($clicked / $sent, 4) : null,
            'links' => $links,
        ];
    }
}
