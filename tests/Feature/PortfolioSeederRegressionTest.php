<?php

use App\Models\Course;
use App\Models\CourseEnrollment;
use App\Models\CourseLesson;
use App\Models\CourseModule;
use App\Models\FinalExamAttempt;
use App\Models\LessonProgress;
use App\Models\User;
use Database\Seeders\ContentSeeder;
use Database\Seeders\DatabaseSeeder;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;

test('repeated content seeding preserves lesson ids progress timestamps and exam history', function () {
    Storage::fake('public');
    $this->seed(ContentSeeder::class);
    $course = Course::query()->where('slug', 'dasar-sel-dan-ekosistem-sma')->firstOrFail();
    $reader = User::factory()->create();
    $lesson = $course->lessons()->firstOrFail();
    $progress = LessonProgress::factory()->for($lesson, 'lesson')->for($reader)->completed()->create();
    $timestamp = $progress->completed_at->toDateTimeString();
    $attemptCount = FinalExamAttempt::query()->count();
    $customModule = CourseModule::factory()->for($course)->create(['title' => 'Pengayaan dari pengajar']);

    $this->seed(ContentSeeder::class);

    expect($lesson->fresh())->not->toBeNull()
        ->and($progress->fresh()?->completed_at?->toDateTimeString())->toBe($timestamp)
        ->and(FinalExamAttempt::query()->count())->toBe($attemptCount)
        ->and($customModule->fresh())->not->toBeNull();
});

test('repeated content seeding never downgrades an existing learner enrollment', function () {
    Storage::fake('public');
    $this->seed(ContentSeeder::class);
    $bram = User::query()->where('email', 'bram@biology.learn')->firstOrFail();
    $enrollment = CourseEnrollment::query()->where('user_id', $bram->id)->firstOrFail();
    $completedAt = now()->subHours(2);
    $enrollment->update(['status' => 'completed', 'completed_at' => $completedAt]);

    $this->seed(ContentSeeder::class);

    expect($enrollment->fresh()->status)->toBe('completed')
        ->and($enrollment->fresh()->completed_at->toDateTimeString())->toBe($completedAt->toDateTimeString());
});

test('database seeder provides a dedicated reader demo with meaningful course data', function () {
    Storage::fake('public');
    $this->seed(DatabaseSeeder::class);
    $demo = User::query()->where('email', 'demo@biology.learn')->first();

    expect($demo)->not->toBeNull();
    expect($demo->role)->toBe('pembaca')
        ->and(Hash::check('BioLearnDemo2026!', $demo->password))->toBeTrue()
        ->and($demo->courseEnrollments()->count())->toBeGreaterThanOrEqual(2)
        ->and($demo->certificates()->count())->toBe(1);

    $progressIds = $demo->lessonProgress()->pluck('id')->all();
    $attemptCount = FinalExamAttempt::query()->count();
    $this->seed(DatabaseSeeder::class);

    expect(User::query()->where('email', 'demo@biology.learn')->count())->toBe(1)
        ->and($demo->lessonProgress()->pluck('id')->all())->toBe($progressIds)
        ->and(FinalExamAttempt::query()->count())->toBe($attemptCount);
});

test('the reserved demo email is always a reader with the documented demo password', function () {
    Storage::fake('public');
    User::factory()->create(['email' => 'demo@biology.learn', 'role' => 'admin', 'password' => 'a-different-password']);

    $this->seed(DatabaseSeeder::class);

    $demo = User::query()->where('email', 'demo@biology.learn')->firstOrFail();
    expect($demo->role)->toBe('pembaca')
        ->and(Hash::check('BioLearnDemo2026!', $demo->password))->toBeTrue();
});

test('repeated seeding preserves teacher edits to existing modules lessons and exam questions', function () {
    Storage::fake('public');
    $this->seed(ContentSeeder::class);
    $course = Course::query()->where('slug', 'dasar-sel-dan-ekosistem-sma')->firstOrFail();
    $module = $course->modules()->firstOrFail();
    $lesson = $module->lessons()->firstOrFail();
    $question = $course->examQuestions()->firstOrFail();
    $module->update(['description' => 'Deskripsi khusus dari pengajar.']);
    $lesson->update(['title' => 'Judul pengayaan dari pengajar', 'is_required' => false]);
    $question->update(['options' => ['Opsi revisi A', 'Opsi revisi B'], 'correct_option_index' => 1]);

    $this->seed(ContentSeeder::class);

    expect($module->fresh()->description)->toBe('Deskripsi khusus dari pengajar.')
        ->and($lesson->fresh()->title)->toBe('Judul pengayaan dari pengajar')
        ->and($lesson->fresh()->is_required)->toBeFalse()
        ->and($question->fresh()->options)->toBe(['Opsi revisi A', 'Opsi revisi B'])
        ->and($question->fresh()->correct_option_index)->toBe(1);
});

test('reseeding never completes a newly added lesson for an existing enrolled learner', function () {
    Storage::fake('public');
    $this->seed(DatabaseSeeder::class);
    $course = Course::query()->where('slug', 'dasar-sel-dan-ekosistem-sma')->firstOrFail();
    $newLesson = CourseLesson::factory()->for($course->modules()->firstOrFail(), 'module')->create();
    $andini = User::query()->where('email', 'andini@biology.learn')->firstOrFail();
    $demo = User::query()->where('email', 'demo@biology.learn')->firstOrFail();

    $this->seed(DatabaseSeeder::class);

    expect(LessonProgress::query()->where('course_lesson_id', $newLesson->id)->whereIn('user_id', [$andini->id, $demo->id])->exists())->toBeFalse();
});
