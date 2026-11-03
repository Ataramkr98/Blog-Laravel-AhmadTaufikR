<?php

namespace Database\Seeders;

use App\Models\Certificate;
use App\Models\Course;
use App\Models\CourseEnrollment;
use App\Models\CourseExamQuestion;
use App\Models\FinalExamAttempt;
use App\Models\LessonProgress;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;

class DemoAccountSeeder extends Seeder
{
    public function run(): void
    {
        DB::transaction(function (): void {
            $demo = User::query()->firstOrCreate(
                ['email' => config('demo.email')],
                [
                    'name' => config('demo.name'),
                    'password' => config('demo.password'),
                    'role' => 'pembaca',
                    'academic_level' => 'sma',
                    'email_verified_at' => now(),
                ],
            );

            if ($demo->role !== 'pembaca' || ! is_string($demo->password) || ! Hash::check(config('demo.password'), $demo->password)) {
                $demo->update(['role' => 'pembaca', 'password' => config('demo.password')]);
            }

            $courses = Course::query()->published()->whereIn('slug', [
                'dasar-sel-dan-ekosistem-sma', 'fotosintesis-dan-evolusi-kehidupan-sma',
            ])->orderBy('sort_order')->get();

            foreach ($courses as $index => $course) {
                $complete = $index === 0;
                $enrollment = CourseEnrollment::query()->firstOrCreate(
                    ['course_id' => $course->id, 'user_id' => $demo->id],
                    ['status' => $complete ? 'completed' : 'active', 'enrolled_at' => now()->subDays(10 - $index * 5), 'completed_at' => $complete ? now()->subDays(2) : null],
                );

                if (! $enrollment->wasRecentlyCreated) {
                    continue;
                }

                $lessons = $course->lessons()->get();
                foreach ($complete ? $lessons : $lessons->take(1) as $lesson) {
                    LessonProgress::query()->firstOrCreate(
                        ['course_lesson_id' => $lesson->id, 'user_id' => $demo->id],
                        ['completed_at' => now()->subDays($complete ? 3 : 1)],
                    );
                }

                $questions = $course->examQuestions()->get();
                if (! $complete || $questions->isEmpty()) {
                    continue;
                }

                $attempt = FinalExamAttempt::query()->firstOrCreate(
                    ['course_id' => $course->id, 'user_id' => $demo->id, 'passed' => true],
                    [
                        'answers' => $questions->map(fn (CourseExamQuestion $question): int => $question->correct_option_index)->all(),
                        'question_snapshot' => $questions->map(fn (CourseExamQuestion $question): array => $question->only(['question', 'options', 'correct_option_index', 'explanation', 'sort_order']))->all(),
                        'score' => $questions->count(),
                        'total_questions' => $questions->count(),
                        'percentage' => 100,
                        'attempted_at' => now()->subDays(2),
                    ],
                );

                Certificate::query()->firstOrCreate(
                    ['course_id' => $course->id, 'user_id' => $demo->id],
                    ['final_exam_attempt_id' => $attempt->id, 'issued_at' => $attempt->attempted_at],
                );
            }
        });
    }
}
