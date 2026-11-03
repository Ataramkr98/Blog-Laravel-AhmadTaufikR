<?php

namespace Database\Factories;

use App\Models\Article;
use App\Models\Citation;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Citation>
 */
class CitationFactory extends Factory
{
    public function definition(): array
    {
        return [
            'article_id' => Article::factory(),
            'citation_text' => fake()->sentence(),
            'doi' => '10. '.fake()->randomNumber(4).'/'.fake()->word().'.'.fake()->randomNumber(4),
            'url' => fake()->url(),
            'sort_order' => fake()->numberBetween(1, 20),
        ];
    }
}
