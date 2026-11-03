<?php

namespace App\Livewire;

use App\Actions\SyncCourseContent;
use App\Models\Article;
use App\Models\Course;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Livewire\Attributes\Locked;
use Livewire\Component;

class AdminCourseManager extends Component
{
    #[Locked]
    public ?int $courseId = null;

    public string $title = '';

    public string $slug = '';

    public string $description = '';

    public string $level = 'sma';

    public string $status = 'draft';

    public int $passingScore = 70;

    public ?string $featuredImage = null;

    public ?string $publishedAt = null;

    /** @var array<int, array{title?: string, description?: string, lessons?: array<int, array{article_id?: int|string|null, title?: string|null, is_required?: bool}>}> */
    public array $modules = [];

    /** @var array<int, array{question?: string, options?: array<int, string>, correct_option_index?: int|string|null, explanation?: string|null}> */
    public array $examQuestions = [];

    public ?string $successMessage = null;

    public function boot(): void
    {
        $this->authorizeAdmin();
    }

    public function mount(): void
    {
        $this->resetForm();
    }

    public function openCreateModal(): void
    {
        $this->resetForm();
    }

    public function addModule(): void
    {
        $this->modules[] = [
            'title' => '',
            'description' => '',
            'lessons' => [],
        ];
    }

    public function addLesson(int $moduleIndex): void
    {
        if (! isset($this->modules[$moduleIndex])) {
            return;
        }
        $this->modules[$moduleIndex]['lessons'][] = [
            'article_id' => null,
            'title' => '',
            'is_required' => true,
        ];
    }

    public function addExamQuestion(): void
    {
        $this->examQuestions[] = [
            'question' => '',
            'options' => ['', ''],
            'correct_option_index' => 0,
            'explanation' => '',
        ];
    }

    public function removeModule(int $index): void
    {
        unset($this->modules[$index]);
        $this->modules = array_values($this->modules);
        $this->resetValidation();
    }

    public function removeLesson(int $moduleIndex, int $lessonIndex): void
    {
        if (isset($this->modules[$moduleIndex]['lessons'][$lessonIndex])) {
            unset($this->modules[$moduleIndex]['lessons'][$lessonIndex]);
            $this->modules[$moduleIndex]['lessons'] = array_values($this->modules[$moduleIndex]['lessons']);
            $this->resetValidation();
        }
    }

    public function removeExamQuestion(int $index): void
    {
        unset($this->examQuestions[$index]);
        $this->examQuestions = array_values($this->examQuestions);
        $this->resetValidation();
    }

    public function addExamOption(int $index): void
    {
        if (isset($this->examQuestions[$index]) && count($this->examQuestions[$index]['options']) < 6) {
            $this->examQuestions[$index]['options'][] = '';
        }
    }

    public function removeExamOption(int $index, int $optionIndex): void
    {
        if (! isset($this->examQuestions[$index]['options'][$optionIndex]) || count($this->examQuestions[$index]['options']) <= 2) {
            return;
        }

        $selected = $this->examQuestions[$index]['correct_option_index'] ?? null;
        $correct = filled($selected) ? (int) $selected : null;
        unset($this->examQuestions[$index]['options'][$optionIndex]);
        $this->examQuestions[$index]['options'] = array_values($this->examQuestions[$index]['options']);
        $this->examQuestions[$index]['correct_option_index'] = $correct === $optionIndex ? null : ($correct !== null && $correct > $optionIndex ? $correct - 1 : $correct);
        $this->resetValidation();
    }

    public function editCourse(int $courseId): void
    {
        $this->authorizeAdmin();
        $this->resetValidation();
        $this->successMessage = null;

        $course = Course::query()
            ->with(['modules.lessons', 'examQuestions'])
            ->findOrFail($courseId);

        $this->courseId = $course->id;
        $this->title = $course->title;
        $this->slug = $course->slug;
        $this->description = $course->description ?? '';
        $this->level = $course->level;
        $this->status = $course->status;
        $this->passingScore = $course->passing_score;
        $this->featuredImage = $course->featured_image;
        $this->publishedAt = $course->published_at?->format('Y-m-d\TH:i');
        $this->modules = $course->modules->map(fn ($module): array => [
            'id' => $module->id,
            'title' => $module->title,
            'description' => $module->description ?? '',
            'lessons' => $module->lessons->map(fn ($lesson): array => [
                'id' => $lesson->id,
                'article_id' => $lesson->article_id,
                'title' => $lesson->title ?? '',
                'is_required' => $lesson->is_required,
            ])->values()->all(),
        ])->values()->all();
        $this->examQuestions = $course->examQuestions->map(fn ($question): array => [
            'id' => $question->id,
            'question' => $question->question,
            'options' => $question->options,
            'correct_option_index' => $question->correct_option_index,
            'explanation' => $question->explanation ?? '',
        ])->values()->all();
    }

    public function saveCourse(): void
    {
        $this->authorizeAdmin();
        $this->slug = $this->slug ?: Str::slug($this->title);

        $validated = $this->validate([
            'title' => ['required', 'string', 'max:255'],
            'slug' => [
                'required',
                'string',
                'max:255',
                'regex:/^[a-z0-9]+(?:-[a-z0-9]+)*$/',
                Rule::unique('courses', 'slug')->ignore($this->courseId),
            ],
            'description' => ['nullable', 'string'],
            'level' => ['required', Rule::in(['sma', 'pt'])],
            'status' => ['required', Rule::in(['draft', 'published', 'archived'])],
            'passingScore' => ['required', 'integer', 'min:1', 'max:100'],
            'featuredImage' => ['nullable', 'string', 'max:255'],
            'publishedAt' => ['nullable', 'date'],
            'modules' => ['required', 'array', 'min:1'],
            'modules.*.id' => ['nullable', 'integer', 'distinct'],
            'modules.*.title' => ['required', 'string', 'max:255'],
            'modules.*.description' => ['nullable', 'string'],
            'modules.*.lessons' => ['required', 'array', 'min:1'],
            'modules.*.lessons.*.id' => ['nullable', 'integer', 'distinct'],
            'modules.*.lessons.*.article_id' => [
                'required',
                'integer',
                Rule::exists('articles', 'id')->where(fn ($query) => $query->where('status', 'published')->where('published_at', '<=', now())),
            ],
            'modules.*.lessons.*.title' => ['nullable', 'string', 'max:255'],
            'modules.*.lessons.*.is_required' => ['boolean'],
            'examQuestions' => ['required', 'array', 'min:1'],
            'examQuestions.*.id' => ['nullable', 'integer', 'distinct'],
            'examQuestions.*.question' => ['required', 'string'],
            'examQuestions.*.options' => ['required', 'array', 'min:2'],
            'examQuestions.*.options.*' => ['required', 'string', 'max:255'],
            'examQuestions.*.correct_option_index' => ['required', 'integer', 'min:0'],
            'examQuestions.*.explanation' => ['nullable', 'string'],
        ]);

        DB::transaction(function () use ($validated): void {
            $course = $this->courseId ? Course::query()->lockForUpdate()->findOrFail($this->courseId) : new Course;

            $course->fill([
                'user_id' => $course->user_id ?? auth()->id(),
                'title' => $validated['title'],
                'slug' => $validated['slug'] ?: Str::slug($validated['title']),
                'description' => $validated['description'] ?: null,
                'level' => $validated['level'],
                'status' => $validated['status'],
                'passing_score' => $validated['passingScore'],
                'featured_image' => $validated['featuredImage'] ?: null,
                'published_at' => $validated['status'] === 'published'
                    ? ($validated['publishedAt'] ? Carbon::parse($validated['publishedAt']) : now())
                    : null,
            ]);
            $course->save();

            app(SyncCourseContent::class)->handle($course, $validated['modules'], $validated['examQuestions']);

            $this->courseId = $course->id;
        });

        $this->editCourse($this->courseId);
        $this->successMessage = 'Kursus berhasil disimpan.';
    }

    public function resetForm(): void
    {
        $this->resetValidation();
        $this->successMessage = null;
        $this->courseId = null;
        $this->title = '';
        $this->slug = '';
        $this->description = '';
        $this->level = 'sma';
        $this->status = 'draft';
        $this->passingScore = 70;
        $this->featuredImage = null;
        $this->publishedAt = null;
        $this->modules = [[
            'title' => '',
            'description' => '',
            'lessons' => [[
                'article_id' => null,
                'title' => '',
                'is_required' => true,
            ]],
        ]];
        $this->examQuestions = [[
            'question' => '',
            'options' => ['', ''],
            'correct_option_index' => 0,
            'explanation' => '',
        ]];
    }

    public function render(): View
    {
        $this->authorizeAdmin();

        return view('admin.livewire.course-manager', [
            'courses' => Course::query()
                ->withCount(['modules', 'examQuestions'])
                ->latest()
                ->get(),
            'articles' => Article::query()
                ->published()
                ->orderBy('title')
                ->get(['id', 'title', 'level']),
        ]);
    }

    private function authorizeAdmin(): void
    {
        abort_unless(auth()->user()?->isAdmin(), 403);
    }
}
