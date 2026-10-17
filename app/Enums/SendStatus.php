<?php

namespace App\Enums;

enum SendStatus: string
{
    case Pending = 'pending';
    // Claimed by a worker. A send never goes back to pending: if a worker dies mid-send, the
    // email may or may not have left, and sending it again risks a duplicate.
    case Sending = 'sending';
    case Sent = 'sent';
    case Failed = 'failed';
    // The subscriber unsubscribed or was suppressed after the campaign started.
    case Skipped = 'skipped';
}
