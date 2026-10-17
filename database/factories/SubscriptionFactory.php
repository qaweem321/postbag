<?php

namespace Database\Factories;

use App\Enums\SubscriptionStatus;
use App\Models\MailingList;
use App\Models\Subscriber;
use App\Models\Subscription;
use Illuminate\Database\Eloquent\Factories\Factory;

/** @extends Factory<Subscription> */
class SubscriptionFactory extends Factory
{
    public function definition(): array
    {
        return [
            'mailing_list_id' => MailingList::factory(),
            'subscriber_id' => Subscriber::factory(),
            'status' => SubscriptionStatus::Subscribed,
            'source' => 'import',
            'confirmed_at' => now(),
        ];
    }
}
