<?php

namespace App\Models;

use App\Enums\SubscriptionStatus;
use Database\Factories\SubscriptionFactory;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;

/**
 * @property int $id
 * @property string $uuid
 * @property int $mailing_list_id
 * @property int $subscriber_id
 * @property SubscriptionStatus $status
 * @property string $source
 * @property Carbon|null $confirmation_sent_at
 * @property Carbon|null $confirmed_at
 * @property Carbon|null $unsubscribed_at
 * @property-read MailingList $mailingList
 * @property-read Subscriber $subscriber
 */
class Subscription extends Model
{
    /** @use HasFactory<SubscriptionFactory> */
    use HasFactory, HasUuids;

    protected $fillable = ['mailing_list_id', 'subscriber_id', 'status', 'source', 'confirmation_sent_at', 'confirmed_at', 'unsubscribed_at'];

    protected function casts(): array
    {
        return [
            'status' => SubscriptionStatus::class,
            'confirmation_sent_at' => 'datetime',
            'confirmed_at' => 'datetime',
            'unsubscribed_at' => 'datetime',
        ];
    }

    /** @return list<string> */
    public function uniqueIds(): array
    {
        return ['uuid'];
    }

    public function getRouteKeyName(): string
    {
        return 'uuid';
    }

    /** @return BelongsTo<MailingList, $this> */
    public function mailingList(): BelongsTo
    {
        return $this->belongsTo(MailingList::class);
    }

    /** @return BelongsTo<Subscriber, $this> */
    public function subscriber(): BelongsTo
    {
        return $this->belongsTo(Subscriber::class);
    }
}
