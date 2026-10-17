<?php

namespace App\Models;

use App\Enums\SubscriptionStatus;
use Database\Factories\MailingListFactory;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * @property int $id
 * @property string $uuid
 * @property string $name
 * @property string|null $description
 * @property bool $double_opt_in
 */
class MailingList extends Model
{
    /** @use HasFactory<MailingListFactory> */
    use HasFactory, HasUuids;

    /** Same defaults as the database, so new models have them before being reloaded. */
    protected $attributes = ['double_opt_in' => true];

    protected $fillable = ['name', 'description', 'double_opt_in'];

    protected function casts(): array
    {
        return ['double_opt_in' => 'boolean'];
    }

    /**
     * Public identifiers are UUIDs, so ids can't be guessed or counted.
     *
     * @return list<string>
     */
    public function uniqueIds(): array
    {
        return ['uuid'];
    }

    public function getRouteKeyName(): string
    {
        return 'uuid';
    }

    /** @return HasMany<Subscription, $this> */
    public function subscriptions(): HasMany
    {
        return $this->hasMany(Subscription::class);
    }

    /** @return HasMany<Subscription, $this> */
    public function activeSubscriptions(): HasMany
    {
        return $this->subscriptions()->where('status', SubscriptionStatus::Subscribed);
    }

    /** @return HasMany<Campaign, $this> */
    public function campaigns(): HasMany
    {
        return $this->hasMany(Campaign::class);
    }
}
