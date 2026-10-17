<?php

namespace App\Enums;

enum CampaignStatus: string
{
    case Draft = 'draft';
    case Scheduled = 'scheduled';
    case Sending = 'sending';
    case Sent = 'sent';
    case Cancelled = 'cancelled';

    public function isEditable(): bool
    {
        return $this === self::Draft || $this === self::Scheduled;
    }

    public function label(): string
    {
        return ucfirst($this->value);
    }
}
