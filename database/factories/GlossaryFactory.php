<?php

namespace Database\Factories;

use App\Models\Glossary;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Glossary>
 */
class GlossaryFactory extends Factory
{
    public function definition(): array
    {
        return [
            'term' => fake()->unique()->words(2, true),
            'common_name' => fake()->word(),
            'definition' => fake()->paragraph(),
            'level' => fake()->randomElement(['sma', 'pt']),
            'category' => fake()->randomElement(['Sitologi', 'Genetika', 'Ekologi', 'Anatomi']),
        ];
    }
}
