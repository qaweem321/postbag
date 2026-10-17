<?php

namespace App\Http\Resources;

use App\Models\Subscription;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/** @mixin Subscription */
class SubscriptionResource extends JsonResource
{
    /** @return array<string, mixed> */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->uuid,
            'email' => $this->subscriber->email,
            'name' => $this->subscriber->name,
            'list_id' => $this->whenLoaded('mailingList', fn () => $this->mailingList->uuid),
            'status' => $this->status->value,
            'source' => $this->source,
            'confirmed_at' => $this->confirmed_at?->toIso8601String(),
            'unsubscribed_at' => $this->unsubscribed_at?->toIso8601String(),
            'created_at' => $this->created_at?->toIso8601String(),
        ];
    }
}
