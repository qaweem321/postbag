<?php

namespace App\Models;

use App\Enums\CampaignStatus;
use Database\Factories\CampaignFactory;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Carbon;

/**
 * @property int $id
 * @property string $uuid
 * @property int $mailing_list_id
 * @property string $name
 * @property string $subject
 * @property string $from_name
 * @property string $from_email
 * @property string $body_markdown
 * @property CampaignStatus $status
 * @property Carbon|null $scheduled_at
 * @property Carbon|null $started_at
 * @property Carbon|null $finished_at
 * @property int $recipients_count
 * @property string|null $batch_id
 * @property-read MailingList $mailingList
 */
class Campaign extends Model
{
    /** @use HasFactory<CampaignFactory> */
    use HasFactory, HasUuids;

    /** Same defaults as the database, so new models have them before being reloaded. */
    protected $attributes = ['status' => 'draft', 'recipients_count' => 0];

    protected $fillable = ['mailing_list_id', 'name', 'subject', 'from_name', 'from_email', 'body_markdown', 'scheduled_at'];

    protected function casts(): array
    {
        return [
            'status' => CampaignStatus::class,
            'scheduled_at' => 'datetime',
            'started_at' => 'datetime',
            'finished_at' => 'datetime',
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

    /** @return HasMany<CampaignSend, $this> */
    public function sends(): HasMany
    {
        return $this->hasMany(CampaignSend::class);
    }

    /** @return HasMany<CampaignLink, $this> */
    public function links(): HasMany
    {
        return $this->hasMany(CampaignLink::class);
    }
}
