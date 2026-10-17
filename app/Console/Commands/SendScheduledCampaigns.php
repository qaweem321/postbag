<?php

namespace App\Console\Commands;

use App\Enums\CampaignStatus;
use App\Models\Campaign;
use App\Services\CampaignSender;
use Illuminate\Console\Command;

class SendScheduledCampaigns extends Command
{
    protected $signature = 'postbag:send-scheduled';

    protected $description = 'Start campaigns whose scheduled time has come';

    public function handle(CampaignSender $sender): int
    {
        $started = 0;
        Campaign::query()
            ->where('status', CampaignStatus::Scheduled)
            ->where('scheduled_at', '<=', now())
            ->orderBy('scheduled_at')
            ->each(function (Campaign $campaign) use ($sender, &$started) {
                $started += (int) $sender->start($campaign);
            });
        $this->info("Started {$started} campaign(s).");

        return self::SUCCESS;
    }
}
