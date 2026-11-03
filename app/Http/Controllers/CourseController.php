<?php

namespace App\Http\Controllers;

use App\Models\Course;
use App\Models\CourseEnrollment;
use App\Models\CourseLesson;
use App\Models\LessonProgress;
use App\Support\GlossaryHighlighter;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class CourseController extends Controller
{
    public function index(): View
    {
        $courses = Course::query()
            ->published()
            ->withCount(['modules', 'examQuestions'])
            ->with(['modules.lessons'])
            ->orderBy('sort_order')
            ->orderByDesc('published_at')
            ->get();

        return view('courses', compact('courses'));
    }

    public function dashboard(Request $request): View
    {
        $user = $request->user();
        $enrollments = CourseEnrollment::query()
            ->where('user_id', $user->id)
            ->with(['course.modules.lessons', 'course.certificates'])
            ->latest('enrolled_at')
            ->get();

        return view('courses.dashboard', compact('enrollments', 'user'));
    }

    public function show(Request $request, Course $course): View
    {
        abort_unless($course->isPublished(), 404);

        $course->load(['modules.lessons.article', 'examQuestions']);
        $user = $request->user();

        return view('courses.show', [
            'course' => $course,
            'enrollment' => $course->enrollmentFor($user),
            'certificate' => $user ? $course->certificateFor($user) : null,
            'requiredLessons' => $course->requiredLessonsCount(),
            'completedLessons' => $user ? $course->completedLessonsCountFor($user) : 0,
            'progressPercent' => $user ? $course->progressPercentFor($user) : 0,
            'examUnlocked' => $user ? $course->hasCompletedRequiredLessons($user) : false,
            'completedLessonIds' => $user
                ? LessonProgress::query()
                    ->where('user_id', $user->id)
                    ->whereNotNull('completed_at')
                    ->pluck('course_lesson_id')
                    ->all()
                : [],
        ]);
    }

    public function enroll(Request $request, Course $course): RedirectResponse
    {
        abort_unless($course->isPublished(), 404);

        CourseEnrollment::query()->firstOrCreate(
            [
                'course_id' => $course->id,
                'user_id' => $request->user()->id,
            ],
            [
                'status' => 'active',
                'enrolled_at' => now(),
            ],
        );

        return redirect()->route('courses.show', $course)
            ->with('success', 'Anda sudah terdaftar di kursus ini. Selamat belajar!');
    }

    public function lesson(Request $request, Course $course, CourseLesson $lesson): View
    {
        abort_unless($course->isPublished(), 404);
        $this->abortUnlessLessonBelongsToCourse($course, $lesson);

        $user = $request->user();
        abort_unless($course->enrollmentFor($user) !== null, 403);

        $lesson->load(['module.course', 'article.category', 'article.tags', 'article.citations', 'article.quizQuestions', 'article.glossaries']);
        $article = $lesson->article;
        abort_unless($article->isPublished(), 404);

        $highlightedContent = app(GlossaryHighlighter::class)
            ->highlight($article->content, $article->glossaries);

        $completed = LessonProgress::query()
            ->where('course_lesson_id', $lesson->id)
            ->where('user_id', $user->id)
            ->whereNotNull('completed_at')
            ->exists();

        return view('courses.lesson', [
            'course' => $course->load('modules.lessons.article'),
            'lesson' => $lesson,
            'article' => $article,
            'highlightedContent' => $highlightedContent,
            'completed' => $completed,
        ]);
    }

    public function completeLesson(Request $request, Course $course, CourseLesson $lesson): RedirectResponse
    {
        abort_unless($course->isPublished(), 404);
        $this->abortUnlessLessonBelongsToCourse($course, $lesson);
        abort_unless($lesson->article->isPublished(), 404);

        $user = $request->user();
        $enrollment = $course->enrollmentFor($user);
        abort_unless($enrollment !== null, 403);

        $progress = LessonProgress::query()->firstOrCreate(
            [
                'course_lesson_id' => $lesson->id,
                'user_id' => $user->id,
            ],
            [
                'completed_at' => now(),
            ],
        );

        if ($progress->completed_at === null) {
            $progress->update(['completed_at' => now()]);
        }

        if ($course->fresh()->hasCompletedRequiredLessons($user)) {
            $enrollment->update([
                'status' => 'completed',
                'completed_at' => $enrollment->completed_at ?? now(),
            ]);
        }

        return redirect()->route('courses.lessons.show', [$course, $lesson])
            ->with('success', 'Lesson ditandai selesai.');
    }

    public function exam(Course $course): View
    {
        abort_unless($course->isPublished(), 404);

        return view('courses.exam', compact('course'));
    }

    private function abortUnlessLessonBelongsToCourse(Course $course, CourseLesson $lesson): void
    {
        abort_unless(
            $lesson->module()->where('course_id', $course->id)->exists(),
            404,
        );
    }
}
