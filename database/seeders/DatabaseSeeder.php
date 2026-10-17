<?php

namespace Database\Seeders;

use App\Enums\SubscriptionStatus;
use App\Models\Campaign;
use App\Models\MailingList;
use App\Models\Subscriber;
use App\Models\Subscription;
use App\Models\User;
use Illuminate\Database\Seeder;

/** Demo data: a login, two lists with subscribers, and a draft campaign ready to send to Mailpit. */
class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        User::query()->firstOrCreate(['email' => 'admin@example.com'], ['name' => 'Admin', 'password' => 'postbag-demo']);
        if (MailingList::query()->exists()) {
            return;
        }

        $digest = MailingList::query()->create(['name' => 'Weekly digest', 'description' => 'The best posts of the week, every Friday.', 'double_opt_in' => true]);
        $releases = MailingList::query()->create(['name' => 'Product updates', 'description' => 'New features and releases.', 'double_opt_in' => false]);

        Subscriber::factory()->count(120)->create()->each(function (Subscriber $subscriber, int $i) use ($digest, $releases) {
            Subscription::query()->create([
                'mailing_list_id' => $digest->id,
                'subscriber_id' => $subscriber->id,
                'status' => match (true) {
                    $i % 17 === 0 => SubscriptionStatus::Unsubscribed, $i % 11 === 0 => SubscriptionStatus::Pending, default => SubscriptionStatus::Subscribed
                },
                'source' => $i % 3 === 0 ? 'wordpress' : 'api',
                'confirmed_at' => now()->subDays($i % 60),
                'unsubscribed_at' => $i % 17 === 0 ? now()->subDays($i % 20) : null,
            ]);
            if ($i % 2 === 0) {
                Subscription::query()->create(['mailing_list_id' => $releases->id, 'subscriber_id' => $subscriber->id, 'status' => SubscriptionStatus::Subscribed, 'source' => 'dashboard', 'confirmed_at' => now()->subDays($i % 30)]);
            }
        });

        Campaign::query()->create([
            'mailing_list_id' => $digest->id,
            'name' => 'Weekly digest #42',
            'subject' => 'This week: queues, indexes and one-click unsubscribe',
            'from_name' => 'Postbag Demo',
            'from_email' => 'digest@example.com',
            'body_markdown' => "Hi {{ name }},\n\nThree things worth reading this week:\n\n1. [Why your queue needs idempotent jobs](https://example.com/idempotent-jobs)\n2. [Indexes MySQL actually uses](https://example.com/mysql-indexes)\n3. [One-click unsubscribe, explained](https://example.com/rfc-8058)\n\nSee you next Friday!",
        ]);
    }
}
