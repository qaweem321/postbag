<?php

namespace App\Enums;

enum SubscribeOutcome: string
{
    case Subscribed = 'subscribed';
    case PendingConfirmation = 'pending_confirmation';
    case AlreadySubscribed = 'already_subscribed';
}
