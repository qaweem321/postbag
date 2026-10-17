<?php

namespace Database\Factories;

use App\Models\Campaign;
use App\Models\MailingList;
use Illuminate\Database\Eloquent\Factories\Factory;

/** @extends Factory<Campaign> */
class CampaignFactory extends Factory
{
    public function definition(): array
    {
        return [
            'mailing_list_id' => MailingList::factory(),
            'name' => 'October newsletter',
            'subject' => 'What we shipped in October',
            'from_name' => 'Postbag',
            'from_email' => 'news@example.com',
            'body_markdown' => "Hi {{ name }},\n\nRead [the changelog](https://example.com/changelog) or [the docs](https://example.com/docs).",
        ];
    }
}
