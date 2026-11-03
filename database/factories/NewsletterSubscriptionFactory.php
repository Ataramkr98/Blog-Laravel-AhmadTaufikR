<?php

namespace Database\Factories;

use App\Models\NewsletterSubscription;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<NewsletterSubscription>
 */
class NewsletterSubscriptionFactory extends Factory
{
    public function definition(): array
    {
        return [
            'user_id' => null,
            'email' => fake()->unique()->safeEmail(),
            'status' => 'subscribed',
            'unsubscribe_token' => Str::random(48),
            'subscribed_at' => now(),
            'unsubscribed_at' => null,
        ];
    }
}
