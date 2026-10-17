<?php

namespace App\Http\Resources;

use App\Models\MailingList;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/** @mixin MailingList */
class MailingListResource extends JsonResource
{
    /** @return array<string, mixed> */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->uuid,
            'name' => $this->name,
            'description' => $this->description,
            'double_opt_in' => $this->double_opt_in,
            'subscribed_count' => $this->whenCounted('activeSubscriptions'),
            'created_at' => $this->created_at?->toIso8601String(),
        ];
    }
}
