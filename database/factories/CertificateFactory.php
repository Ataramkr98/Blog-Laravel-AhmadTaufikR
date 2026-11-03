<?php

namespace Database\Factories;

use App\Models\Certificate;
use App\Models\Course;
use App\Models\FinalExamAttempt;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Certificate>
 */
class CertificateFactory extends Factory
{
    public function definition(): array
    {
        return [
            'course_id' => Course::factory(),
            'user_id' => User::factory(),
            'final_exam_attempt_id' => FinalExamAttempt::factory(),
            'certificate_number' => Certificate::generateNumber(),
            'issued_at' => now(),
            'revoked_at' => null,
        ];
    }
}
