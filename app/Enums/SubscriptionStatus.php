<?php

namespace App\Enums;

enum SubscriptionStatus: string
{
    // Waiting for the subscriber to click the confirmation link (double opt-in).
    case Pending = 'pending';
    case Subscribed = 'subscribed';
    case Unsubscribed = 'unsubscribed';
}
