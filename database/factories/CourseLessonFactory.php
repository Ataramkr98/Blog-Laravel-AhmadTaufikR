<?php

namespace Database\Factories;

use App\Models\Article;
use App\Models\CourseLesson;
use App\Models\CourseModule;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<CourseLesson>
 */
class CourseLessonFactory extends Factory
{
    public function definition(): array
    {
        return [
            'course_module_id' => CourseModule::factory(),
            'article_id' => Article::factory(),
            'title' => fake()->optional()->sentence(4),
            'sort_order' => fake()->numberBetween(1, 10),
            'is_required' => true,
        ];
    }
}
