<?php

use App\Livewire\AdminArticleManager;
use App\Livewire\AdminCourseManager;
use App\Livewire\CourseFinalExam;
use App\Models\Article;
use App\Models\Certificate;
use App\Models\Course;
use App\Models\CourseEnrollment;
use App\Models\CourseExamQuestion;
use App\Models\CourseLesson;
use App\Models\CourseModule;
use App\Models\FinalExamAttempt;
use App\Models\LessonProgress;
use App\Models\User;
use Livewire\Features\SupportLockedProperties\CannotUpdateLockedPropertyException;
use Livewire\Livewire;

function stageTwoCourse(): array
{
    $article = Article::factory()->create();
    $course = Course::factory()->published()->create();
    $module = CourseModule::factory()->for($course)->create();
    $lesson = CourseLesson::factory()->for($module, 'module')->for($article)->create(['is_required' => true]);
    $question = CourseExamQuestion::factory()->for($course)->create([
        'options' => ['Mitokondria', 'Ribosom'],
        'correct_option_index' => 0,
    ]);

    return [$course, $module, $lesson, $question, $article];
}

test('editing course metadata preserves lesson identity and student progress', function () {
    [$course, $module, $lesson, $question] = stageTwoCourse();
    $reader = User::factory()->create();
    $admin = User::factory()->create(['role' => 'admin']);
    $progress = LessonProgress::factory()->for($lesson, 'lesson')->for($reader)->completed()->create();

    Livewire::actingAs($admin)->test(AdminCourseManager::class)
        ->call('editCourse', $course->id)
        ->set('description', 'Materi diperbarui tanpa mengulang progres siswa.')
        ->call('saveCourse')
        ->assertHasNoErrors();

    expect($progress->fresh())->not->toBeNull()
        ->and($course->fresh()->modules->first()->id)->toBe($module->id)
        ->and($course->fresh()->lessons->first()->id)->toBe($lesson->id)
        ->and($course->fresh()->examQuestions->first()->id)->toBe($question->id);
});

test('removing a lesson with student progress is rejected without partial changes', function () {
    [$course, , $lesson, , $article] = stageTwoCourse();
    LessonProgress::factory()->for($lesson, 'lesson')->completed()->create();
    $admin = User::factory()->create(['role' => 'admin']);

    Livewire::actingAs($admin)->test(AdminCourseManager::class)
        ->call('editCourse', $course->id)
        ->set('description', 'This change must roll back')
        ->set('modules', [[
            'title' => 'Modul pengganti',
            'description' => '',
            'lessons' => [['article_id' => $article->id, 'title' => 'Materi baru', 'is_required' => true]],
        ]])
        ->call('saveCourse')
        ->assertHasErrors('modules');

    expect($lesson->fresh())->not->toBeNull()
        ->and($course->fresh()->description)->not->toBe('This change must roll back');
});

test('enrolled readers cannot read or complete lessons backed by unpublished articles', function (string $status) {
    [$course, , $lesson, , $article] = stageTwoCourse();
    $reader = User::factory()->create();
    CourseEnrollment::factory()->for($course)->for($reader)->create();
    $article->update(['status' => $status, 'published_at' => null]);

    $this->actingAs($reader)->get(route('courses.lessons.show', [$course, $lesson]))->assertNotFound();
    $this->post(route('courses.lessons.complete', [$course, $lesson]))->assertNotFound();
    expect(LessonProgress::query()->count())->toBe(0);
})->with(['draft', 'archived']);

test('an exam opened before a course is archived cannot issue an attempt or certificate', function () {
    [$course, , $lesson] = stageTwoCourse();
    $reader = User::factory()->create();
    CourseEnrollment::factory()->for($course)->for($reader)->create();
    LessonProgress::factory()->for($lesson, 'lesson')->for($reader)->completed()->create();
    $exam = Livewire::actingAs($reader)->test(CourseFinalExam::class, ['courseId' => $course->id]);
    $course->update(['status' => 'archived']);

    $exam->call('submitExam')->assertNotFound();

    expect(FinalExamAttempt::query()->count())->toBe(0)
        ->and(Certificate::query()->count())->toBe(0);
});

test('exam result lookup is scoped to its owner and course', function () {
    [$course] = stageTwoCourse();
    $reader = User::factory()->create();
    $foreignAttempt = FinalExamAttempt::factory()->for($course)->create();
    $this->actingAs($reader);
    $component = new CourseFinalExam;
    $component->mount($course->id);
    $component->lastAttemptId = $foreignAttempt->id;

    expect($component->render()->getData()['attempt'])->toBeNull();
});

test('clients cannot replace the last exam attempt identifier', function () {
    [$course] = stageTwoCourse();
    $reader = User::factory()->create();

    Livewire::actingAs($reader)->test(CourseFinalExam::class, ['courseId' => $course->id])
        ->set('lastAttemptId', 999);
})->throws(CannotUpdateLockedPropertyException::class);

test('incomplete or invalid exam answers do not create an attempt', function () {
    [$course, , $lesson] = stageTwoCourse();
    $reader = User::factory()->create();
    CourseEnrollment::factory()->for($course)->for($reader)->create();
    LessonProgress::factory()->for($lesson, 'lesson')->for($reader)->completed()->create();

    Livewire::actingAs($reader)->test(CourseFinalExam::class, ['courseId' => $course->id])
        ->call('submitExam')->assertHasErrors('exam')
        ->set('answers', [99])->call('submitExam')->assertHasErrors('exam');

    expect(FinalExamAttempt::query()->count())->toBe(0);
});

test('repeated submit of the same exam result is idempotent', function () {
    [$course, , $lesson] = stageTwoCourse();
    $reader = User::factory()->create();
    CourseEnrollment::factory()->for($course)->for($reader)->create();
    LessonProgress::factory()->for($lesson, 'lesson')->for($reader)->completed()->create();

    Livewire::actingAs($reader)->test(CourseFinalExam::class, ['courseId' => $course->id])
        ->call('selectAnswer', 0, 0)->call('submitExam')->call('submitExam');

    expect(FinalExamAttempt::query()->count())->toBe(1)
        ->and(Certificate::query()->count())->toBe(1);
});

test('submitted exam feedback preserves the original questions and answers after content edits', function () {
    [$course, , $lesson, $question] = stageTwoCourse();
    $originalQuestion = $question->question;
    $reader = User::factory()->create();
    CourseEnrollment::factory()->for($course)->for($reader)->create();
    LessonProgress::factory()->for($lesson, 'lesson')->for($reader)->completed()->create();

    $exam = Livewire::actingAs($reader)->test(CourseFinalExam::class, ['courseId' => $course->id])
        ->call('selectAnswer', 0, 0)->call('submitExam');
    $question->update(['question' => 'Pertanyaan yang sudah diganti']);

    $exam->set('answers', [1])->assertSee($originalQuestion)->assertDontSee('Pertanyaan yang sudah diganti')
        ->assertSet('answers', [0]);
});

test('completing an existing unfinished progress record is idempotent', function () {
    [$course, , $lesson] = stageTwoCourse();
    $reader = User::factory()->create();
    CourseEnrollment::factory()->for($course)->for($reader)->create();
    $progress = LessonProgress::factory()->for($lesson, 'lesson')->for($reader)->create(['completed_at' => null]);

    $this->actingAs($reader)->post(route('courses.lessons.complete', [$course, $lesson]))->assertRedirect();
    expect($progress->fresh()->completed_at)->not->toBeNull();
    $completedAt = $progress->fresh()->completed_at->toDateTimeString();
    $this->travel(1)->hours();
    $this->post(route('courses.lessons.complete', [$course, $lesson]))->assertRedirect();
    expect($progress->fresh()->completed_at->toDateTimeString())->toBe($completedAt);
});

test('deleting an article referenced by a course cannot cascade away student progress', function () {
    [$course, , $lesson, , $article] = stageTwoCourse();
    $progress = LessonProgress::factory()->for($lesson, 'lesson')->completed()->create();
    $admin = User::factory()->create(['role' => 'admin']);

    Livewire::actingAs($admin)->test(AdminArticleManager::class)
        ->call('deleteArticle', $article->id)->assertHasErrors('article');

    expect($article->fresh())->not->toBeNull()
        ->and($lesson->fresh())->not->toBeNull()
        ->and($progress->fresh())->not->toBeNull();
});

test('new modules and lessons are never silently merged merely because their labels match', function () {
    $admin = User::factory()->create(['role' => 'admin']);
    $article = Article::factory()->create();
    $module = [
        'title' => 'Biologi Dasar', 'description' => '',
        'lessons' => [
            ['article_id' => $article->id, 'title' => 'Pengantar', 'is_required' => true],
            ['article_id' => $article->id, 'title' => 'Tinjauan', 'is_required' => true],
        ],
    ];

    Livewire::actingAs($admin)->test(AdminCourseManager::class)
        ->set('title', 'Kursus untuk dua kelas')->set('modules', [$module, $module])
        ->set('examQuestions', [['question' => 'Apa fungsi DNA?', 'options' => ['Informasi genetik', 'Pigmen'], 'correct_option_index' => 0, 'explanation' => 'DNA menyimpan informasi genetik.']])
        ->call('saveCourse')->assertHasNoErrors();

    $course = Course::query()->where('slug', 'kursus-untuk-dua-kelas')->firstOrFail();
    expect($course->modules()->count())->toBe(2)->and($course->lessons()->count())->toBe(4);
});

test('an exam changed while a learner is answering must be reopened before grading', function () {
    [$course, , $lesson, $question] = stageTwoCourse();
    $reader = User::factory()->create();
    CourseEnrollment::factory()->for($course)->for($reader)->create();
    LessonProgress::factory()->for($lesson, 'lesson')->for($reader)->completed()->create();
    $exam = Livewire::actingAs($reader)->test(CourseFinalExam::class, ['courseId' => $course->id]);
    $question->update(['correct_option_index' => 1]);

    $exam->call('selectAnswer', 0, 0)->call('submitExam')->assertHasErrors('exam');
    expect(FinalExamAttempt::query()->count())->toBe(0);
});
