<?php

namespace App\Models;

use App\Enums\SubscriberStatus;
use Database\Factories\SubscriberFactory;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Carbon;

/**
 * @property int $id
 * @property string $uuid
 * @property string $email
 * @property string|null $name
 * @property SubscriberStatus $status
 * @property Carbon|null $suppressed_at
 */
class Subscriber extends Model
{
    /** @use HasFactory<SubscriberFactory> */
    use HasFactory, HasUuids;

    /** Same defaults as the database, so new models have them before being reloaded. */
    protected $attributes = ['status' => 'active'];

    protected $fillable = ['email', 'name', 'status', 'suppressed_at'];

    protected function casts(): array
    {
        return ['status' => SubscriberStatus::class, 'suppressed_at' => 'datetime'];
    }

    /** @return list<string> */
    public function uniqueIds(): array
    {
        return ['uuid'];
    }

    public static function normalizeEmail(string $email): string
    {
        return mb_strtolower(trim($email));
    }

    /**
     * Emails are stored lowercase, so lookups and the unique index ignore case.
     *
     * @return Attribute<string, string>
     */
    protected function email(): Attribute
    {
        return Attribute::set(fn (string $value) => self::normalizeEmail($value));
    }

    /** @return HasMany<Subscription, $this> */
    public function subscriptions(): HasMany
    {
        return $this->hasMany(Subscription::class);
    }
}
