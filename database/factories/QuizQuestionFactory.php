<?php

namespace Database\Factories;

use App\Models\Article;
use App\Models\QuizQuestion;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<QuizQuestion>
 */
class QuizQuestionFactory extends Factory
{
    public function definition(): array
    {
        $options = fake()->words(4);

        return [
            'article_id' => Article::factory(),
            'question' => fake()->sentence(),
            'options' => $options,
            'correct_option_index' => 0,
            'explanation' => fake()->paragraph(),
            'sort_order' => fake()->numberBetween(1, 20),
        ];
    }
}
