<?php

namespace Database\Factories;

use App\Models\Course;
use App\Models\CourseExamQuestion;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<CourseExamQuestion>
 */
class CourseExamQuestionFactory extends Factory
{
    public function definition(): array
    {
        return [
            'course_id' => Course::factory(),
            'question' => fake()->sentence(),
            'options' => fake()->words(4),
            'correct_option_index' => 0,
            'explanation' => fake()->paragraph(),
            'sort_order' => fake()->numberBetween(1, 10),
        ];
    }
}
