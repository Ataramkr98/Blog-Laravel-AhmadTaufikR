<?php

use App\Livewire\AdminCourseManager;
use App\Livewire\CourseFinalExam;
use App\Models\Article;
use App\Models\Course;
use App\Models\CourseEnrollment;
use App\Models\CourseExamQuestion;
use App\Models\CourseLesson;
use App\Models\CourseModule;
use App\Models\LessonProgress;
use App\Models\User;
use Livewire\Livewire;

function makePublishedCourseWithLesson(?Article $article = null): array
{
    $article ??= Article::factory()->create();
    $course = Course::factory()->published()->create([
        'title' => 'Biologi Sel Terapan',
        'slug' => 'biologi-sel-terapan',
        'passing_score' => 70,
    ]);
    $module = CourseModule::factory()->for($course)->create([
        'title' => 'Fondasi Sel',
        'sort_order' => 1,
    ]);
    $lesson = CourseLesson::factory()->for($module, 'module')->for($article)->create([
        'title' => 'Membran dan Organel',
        'sort_order' => 1,
        'is_required' => true,
    ]);

    return [$course, $module, $lesson, $article];
}

test('course catalog only shows published courses', function () {
    Course::factory()->published()->create(['title' => 'Kursus Biologi Aktif']);
    Course::factory()->create([
        'title' => 'Kursus Draft Tersembunyi',
        'status' => 'draft',
        'published_at' => null,
    ]);

    $this->get(route('courses'))
        ->assertOk()
        ->assertSee('Kursus Biologi Aktif')
        ->assertDontSee('Kursus Draft Tersembunyi');
});

test('reader can enroll, open lesson, complete it, and see progress', function () {
    [$course, $module, $lesson] = makePublishedCourseWithLesson();
    $reader = User::factory()->create(['role' => 'pembaca']);

    $this->actingAs($reader)
        ->post(route('courses.enroll', $course))
        ->assertRedirect(route('courses.show', $course));

    $this->assertDatabaseHas('course_enrollments', [
        'course_id' => $course->id,
        'user_id' => $reader->id,
        'status' => 'active',
    ]);

    $this->actingAs($reader)
        ->get(route('courses.lessons.show', [$course, $lesson]))
        ->assertOk()
        ->assertSee($module->title)
        ->assertSee($lesson->article->title);

    $this->actingAs($reader)
        ->post(route('courses.lessons.complete', [$course, $lesson]))
        ->assertRedirect(route('courses.lessons.show', [$course, $lesson]));

    $this->assertDatabaseHas('lesson_progress', [
        'course_lesson_id' => $lesson->id,
        'user_id' => $reader->id,
    ]);

    $this->actingAs($reader)
        ->get(route('courses.show', $course))
        ->assertOk()
        ->assertSee('1/1 lesson selesai');

    $this->actingAs($reader)
        ->get(route('courses.dashboard'))
        ->assertOk()
        ->assertSee($course->title)
        ->assertSee('100%');
});

test('final exam unlocks after lessons and issues one web certificate after passing', function () {
    [$course, , $lesson] = makePublishedCourseWithLesson();
    $reader = User::factory()->create(['role' => 'pembaca']);
    CourseEnrollment::factory()->for($course)->for($reader)->create();
    CourseExamQuestion::factory()->for($course)->create([
        'question' => 'Organel penghasil ATP adalah?',
        'options' => ['Mitokondria', 'Ribosom'],
        'correct_option_index' => 0,
        'explanation' => 'Mitokondria melakukan respirasi seluler.',
        'sort_order' => 1,
    ]);

    Livewire::actingAs($reader)
        ->test(CourseFinalExam::class, ['courseId' => $course->id])
        ->assertSee('Selesaikan semua lesson');

    LessonProgress::factory()->for($lesson, 'lesson')->for($reader)->completed()->create();

    Livewire::actingAs($reader)
        ->test(CourseFinalExam::class, ['courseId' => $course->id])
        ->call('selectAnswer', 0, 1)
        ->call('submitExam')
        ->assertSee('0%')
        ->assertSee('Belum Lulus');

    Livewire::actingAs($reader)
        ->test(CourseFinalExam::class, ['courseId' => $course->id])
        ->call('selectAnswer', 0, 0)
        ->call('submitExam')
        ->assertSee('100%')
        ->assertSee('Lulus')
        ->assertSee('Lihat Sertifikat');

    Livewire::actingAs($reader)
        ->test(CourseFinalExam::class, ['courseId' => $course->id])
        ->call('selectAnswer', 0, 0)
        ->call('submitExam');

    expect($course->finalExamAttempts()->where('user_id', $reader->id)->count())->toBe(3)
        ->and($course->certificates()->where('user_id', $reader->id)->count())->toBe(1);

    $certificate = $course->certificates()->where('user_id', $reader->id)->firstOrFail();

    $this->get(route('certificates.show', $certificate))
        ->assertOk()
        ->assertSee($certificate->certificate_number)
        ->assertSee($reader->name)
        ->assertSee($course->title);
});

test('admin can create a course with modules lessons and exam questions', function () {
    $admin = User::factory()->create(['role' => 'admin']);
    $writer = User::factory()->create(['role' => 'penulis']);
    $article = Article::factory()->create();

    Livewire::actingAs($admin)
        ->test(AdminCourseManager::class)
        ->set('title', 'Kursus Genetika')
        ->set('slug', 'kursus-genetika')
        ->set('description', 'Belajar genetika dari artikel terkurasi.')
        ->set('level', 'pt')
        ->set('status', 'published')
        ->set('passingScore', 70)
        ->set('modules', [[
            'title' => 'DNA Dasar',
            'description' => 'Modul pembuka',
            'lessons' => [[
                'article_id' => $article->id,
                'title' => 'Struktur DNA',
                'is_required' => true,
            ]],
        ]])
        ->set('examQuestions', [[
            'question' => 'Apa materi genetik utama?',
            'options' => ['DNA', 'Lipid'],
            'correct_option_index' => 0,
            'explanation' => 'DNA membawa informasi genetik.',
        ]])
        ->call('saveCourse')
        ->assertHasNoErrors();

    $this->assertDatabaseHas('courses', [
        'slug' => 'kursus-genetika',
        'status' => 'published',
    ]);
    $this->assertDatabaseHas('course_lessons', ['article_id' => $article->id]);
    $this->assertDatabaseHas('course_exam_questions', ['question' => 'Apa materi genetik utama?']);

    $this->actingAs($writer)
        ->get(route('admin.courses'))
        ->assertForbidden();
});
