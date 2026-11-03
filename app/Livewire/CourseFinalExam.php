<?php

namespace App\Livewire;

use App\Models\Certificate;
use App\Models\Course;
use App\Models\CourseExamQuestion;
use App\Models\FinalExamAttempt;
use Illuminate\Contracts\View\View;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Str;
use Livewire\Attributes\Locked;
use Livewire\Component;

class CourseFinalExam extends Component
{
    #[Locked]
    public int $courseId;

    /** @var array<int, int|null> */
    public array $answers = [];

    #[Locked]
    public bool $showResults = false;

    #[Locked]
    public ?int $lastAttemptId = null;

    #[Locked]
    public string $submissionToken;

    #[Locked]
    public string $questionFingerprint;

    public function mount(int $courseId): void
    {
        $this->courseId = $courseId;
        $this->retry();
    }

    public function selectAnswer(int $questionIndex, int $optionIndex): void
    {
        if (! $this->showResults && $questionIndex >= 0 && $optionIndex >= 0) {
            $this->answers[$questionIndex] = $optionIndex;
        }
    }

    public function submitExam(): void
    {
        $this->resetErrorBag('exam');
        $user = auth()->user();
        $course = Course::query()->findOrFail($this->courseId);
        abort_unless($course->isPublished(), 404);

        if (! $user || ! $course->enrollmentFor($user)) {
            $this->addError('exam', 'Anda perlu enroll sebelum mengikuti ujian akhir.');

            return;
        }

        if ($this->lastAttemptId !== null) {
            return;
        }

        if (! $course->hasCompletedRequiredLessons($user)) {
            $this->addError('exam', 'Selesaikan semua lesson sebelum membuka ujian akhir.');

            return;
        }

        $questions = $course->examQuestions()->get();
        if (! hash_equals($this->questionFingerprint, $this->fingerprint($course, $questions))) {
            $this->addError('exam', 'Materi ujian diperbarui. Muat ulang halaman sebelum menjawab kembali.');

            return;
        }
        $validAnswers = $questions->isNotEmpty()
            && array_is_list($this->answers)
            && count($this->answers) === $questions->count()
            && $questions->every(function (CourseExamQuestion $question, int $index): bool {
                $answer = $this->answers[$index] ?? null;

                return is_int($answer) && array_key_exists($answer, $question->options);
            });

        if (! $validAnswers) {
            $this->addError('exam', 'Pilih satu jawaban yang valid untuk setiap soal sebelum mengirim ujian.');

            return;
        }

        $rateKey = 'final-exam:'.$user->id.':'.$course->id;
        if (RateLimiter::tooManyAttempts($rateKey, 10)) {
            $this->addError('exam', 'Terlalu banyak percobaan ujian. Silakan coba kembali beberapa menit lagi.');

            return;
        }
        RateLimiter::hit($rateKey, 600);

        $attempt = DB::transaction(function () use ($course, $questions, $user): FinalExamAttempt {
            $score = $questions->filter(fn (CourseExamQuestion $question, int $index): bool => $this->answers[$index] === $question->correct_option_index)->count();
            $total = $questions->count();
            $percentage = (int) round(($score / $total) * 100);

            $attempt = FinalExamAttempt::query()->firstOrCreate(
                ['course_id' => $course->id, 'user_id' => $user->id, 'submission_token' => $this->submissionToken],
                [
                    'answers' => $this->answers,
                    'question_snapshot' => $questions->map(fn (CourseExamQuestion $question): array => $question->only([
                        'question', 'options', 'correct_option_index', 'explanation', 'sort_order',
                    ]))->values()->all(),
                    'score' => $score,
                    'total_questions' => $total,
                    'percentage' => $percentage,
                    'passed' => $percentage >= $course->passing_score,
                    'attempted_at' => now(),
                ],
            );

            if ($attempt->passed) {
                Certificate::query()->firstOrCreate(
                    ['course_id' => $course->id, 'user_id' => $user->id],
                    ['final_exam_attempt_id' => $attempt->id, 'issued_at' => now()],
                );
            }

            return $attempt;
        });

        $this->lastAttemptId = $attempt->id;
        $this->showResults = true;
    }

    public function retry(): void
    {
        $this->resetErrorBag('exam');
        $this->showResults = false;
        $this->lastAttemptId = null;
        $this->submissionToken = (string) Str::uuid();
        $course = Course::query()->with('examQuestions')->findOrFail($this->courseId);
        $this->questionFingerprint = $this->fingerprint($course, $course->examQuestions);
        $this->answers = array_fill(0, $course->examQuestions->count(), null);
    }

    /** @param Collection<int, CourseExamQuestion> $questions */
    private function fingerprint(Course $course, Collection $questions): string
    {
        return hash_hmac('sha256', json_encode([
            'passing_score' => $course->passing_score,
            'questions' => $questions->map(fn (CourseExamQuestion $question): array => $question->only([
                'id', 'question', 'options', 'correct_option_index', 'explanation', 'sort_order',
            ]))->all(),
        ], JSON_THROW_ON_ERROR), config('app.key'));
    }

    public function render(): View
    {
        $course = Course::query()
            ->with('examQuestions')
            ->findOrFail($this->courseId);
        abort_unless($course->isPublished(), 404);
        $user = auth()->user();
        $attempt = $user && $this->lastAttemptId
            ? $course->finalExamAttempts()->where('user_id', $user->id)->find($this->lastAttemptId)
            : null;

        $questions = $attempt?->question_snapshot
            ? collect($attempt->question_snapshot)->map(fn (array $question): CourseExamQuestion => new CourseExamQuestion($question))
            : $course->examQuestions;

        if ($attempt) {
            $this->answers = $attempt->answers;
        }

        return view('livewire.course-final-exam', [
            'course' => $course,
            'questions' => $questions,
            'enrollment' => $user ? $course->enrollmentFor($user) : null,
            'examUnlocked' => $user ? $course->hasCompletedRequiredLessons($user) : false,
            'attempt' => $attempt,
            'certificate' => $user ? $course->certificateFor($user) : null,
        ]);
    }
}
