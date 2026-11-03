<?php

namespace Database\Factories;

use App\Models\Article;
use App\Models\Category;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<Article>
 */
class ArticleFactory extends Factory
{
    public function definition(): array
    {
        $title = fake()->unique()->sentence(8);

        return [
            'user_id' => User::factory(),
            // Default to an SMA category so the explicit 'sma' level below is
            // preserved by Article::boot() (which derives level from category).
            // Use ->pt() when a university article is needed.
            'category_id' => Category::factory()->sma(),
            'level' => 'sma',
            'title' => $title,
            'slug' => Str::slug($title),
            'excerpt' => fake()->paragraph(),
            'content' => implode("\n\n", fake()->paragraphs(8)),
            'featured_image' => fake()->imageUrl(1200, 630, 'science', true, 'biology'),
            'status' => 'published',
            'published_at' => now()->subDays(fake()->numberBetween(1, 30)),
            'view_count' => fake()->numberBetween(100, 10000),
        ];
    }

    /**
     * A university (Perguruan Tinggi) article: pairs the 'pt' level with a PT
     * category so Article::boot()'s category-derived level stays consistent.
     */
    public function pt(): static
    {
        return $this->state(fn (array $attributes) => [
            'category_id' => Category::factory()->pt(),
            'level' => 'pt',
        ]);
    }

    public function draft(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => 'draft',
            'published_at' => null,
        ]);
    }
}
