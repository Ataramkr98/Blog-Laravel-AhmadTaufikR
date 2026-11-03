<?php

namespace Database\Factories;

use App\Models\Category;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<Category>
 */
class CategoryFactory extends Factory
{
    public function definition(): array
    {
        $name = fake()->unique()->words(2, true);

        return [
            'name' => $name,
            'slug' => Str::slug($name),
            'level' => 'sma',
            'description' => fake()->sentence(),
            'sort_order' => fake()->numberBetween(1, 100),
        ];
    }

    /**
     * A high-school (SMA) level category. This is the deterministic default;
     * callers that need a university (PT) category should use ->pt().
     */
    public function sma(): static
    {
        return $this->state(fn (array $attributes) => ['level' => 'sma']);
    }

    /**
     * A university (Perguruan Tinggi) level category.
     */
    public function pt(): static
    {
        return $this->state(fn (array $attributes) => ['level' => 'pt']);
    }
}
