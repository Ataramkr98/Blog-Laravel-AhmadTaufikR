<?php

namespace Database\Factories;

use App\Models\Course;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<Course>
 */
class CourseFactory extends Factory
{
    public function definition(): array
    {
        $title = fake()->unique()->sentence(4);

        return [
            'user_id' => User::factory(),
            'title' => $title,
            'slug' => Str::slug($title),
            'description' => fake()->paragraph(),
            'level' => fake()->randomElement(['sma', 'pt']),
            'featured_image' => fake()->imageUrl(1200, 630, 'biology', true),
            'status' => 'draft',
            'passing_score' => 70,
            'published_at' => null,
            'sort_order' => fake()->numberBetween(1, 20),
        ];
    }

    public function published(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => 'published',
            'published_at' => now()->subDays(fake()->numberBetween(1, 14)),
        ]);
    }
}
