<?php

namespace Database\Factories;

use App\Models\Course;
use App\Models\FinalExamAttempt;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<FinalExamAttempt>
 */
class FinalExamAttemptFactory extends Factory
{
    public function definition(): array
    {
        return [
            'course_id' => Course::factory(),
            'user_id' => User::factory(),
            'answers' => [],
            'score' => 0,
            'total_questions' => 0,
            'percentage' => 0,
            'passed' => false,
            'attempted_at' => now(),
        ];
    }
}
