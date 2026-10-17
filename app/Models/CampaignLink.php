<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * @property int $id
 * @property int $campaign_id
 * @property string $url
 * @property string $url_hash
 * @property int $click_count
 */
class CampaignLink extends Model
{
    /** Same defaults as the database, so new models have them before being reloaded. */
    protected $attributes = ['click_count' => 0];

    protected $fillable = ['campaign_id', 'url', 'url_hash'];

    /** @return BelongsTo<Campaign, $this> */
    public function campaign(): BelongsTo
    {
        return $this->belongsTo(Campaign::class);
    }
}
