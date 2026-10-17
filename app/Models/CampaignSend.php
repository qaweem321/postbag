<?php

namespace App\Models;

use App\Enums\SendStatus;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;

/**
 * @property int $id
 * @property string $uuid
 * @property int $campaign_id
 * @property int $subscriber_id
 * @property int $subscription_id
 * @property SendStatus $status
 * @property string|null $error
 * @property Carbon|null $sent_at
 * @property Carbon|null $opened_at
 * @property int $open_count
 * @property Carbon|null $clicked_at
 * @property int $click_count
 * @property-read Campaign $campaign
 * @property-read Subscriber $subscriber
 * @property-read Subscription $subscription
 */
class CampaignSend extends Model
{
    use HasUuids;

    protected $guarded = [];

    protected function casts(): array
    {
        return [
            'status' => SendStatus::class,
            'sent_at' => 'datetime',
            'opened_at' => 'datetime',
            'clicked_at' => 'datetime',
        ];
    }

    public function uniqueIds(): array
    {
        return ['uuid'];
    }

    public function getRouteKeyName(): string
    {
        return 'uuid';
    }

    /** @return BelongsTo<Campaign, $this> */
    public function campaign(): BelongsTo
    {
        return $this->belongsTo(Campaign::class);
    }

    /** @return BelongsTo<Subscriber, $this> */
    public function subscriber(): BelongsTo
    {
        return $this->belongsTo(Subscriber::class);
    }

    /** @return BelongsTo<Subscription, $this> */
    public function subscription(): BelongsTo
    {
        return $this->belongsTo(Subscription::class);
    }
}
