<?php

namespace App\Enums;

enum SubscriberStatus: string
{
    case Active = 'active';
    // Suppressed addresses never receive email again, from any list.
    case Bounced = 'bounced';
    case Complained = 'complained';

    public function isSuppressed(): bool
    {
        return $this !== self::Active;
    }
}
