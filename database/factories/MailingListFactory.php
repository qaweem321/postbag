<?php

namespace Database\Factories;

use App\Models\MailingList;
use Illuminate\Database\Eloquent\Factories\Factory;

/** @extends Factory<MailingList> */
class MailingListFactory extends Factory
{
    public function definition(): array
    {
        return ['name' => ucfirst(fake()->words(2, true)), 'description' => fake()->sentence(), 'double_opt_in' => true];
    }

    public function singleOptIn(): static
    {
        return $this->state(['double_opt_in' => false]);
    }
}
