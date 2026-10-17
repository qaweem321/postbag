<?php

return [
    // Recipients per queued job. Each job sends its emails one by one.
    'batch_size' => (int) env('POSTBAG_BATCH_SIZE', 100),

    // Upper limit on emails per minute across all workers, to stay within the mail provider's limits.
    'emails_per_minute' => (int) env('POSTBAG_EMAILS_PER_MINUTE', 600),

    // Default sender for new campaigns.
    'from_name' => env('MAIL_FROM_NAME', 'Postbag'),
    'from_email' => env('MAIL_FROM_ADDRESS', 'newsletter@example.com'),
];
