<?php

namespace App\Livewire;

use App\Models\Article;
use App\Models\Category;
use App\Models\Citation;
use App\Models\Glossary;
use App\Models\MediaAsset;
use App\Models\QuizQuestion;
use App\Models\Tag;
use App\Support\ArticleHtmlSanitizer;
use App\Support\WebpMediaStorage;
use Carbon\Carbon;
use Illuminate\Contracts\View\View;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Livewire\Attributes\Url;
use Livewire\Component;
use Livewire\WithFileUploads;
use Livewire\WithPagination;
use RuntimeException;

class AdminArticleManager extends Component
{
    use WithFileUploads, WithPagination;

    #[Url(except: '')]
    public ?string $search = '';

    public function updatedSearch(): void
    {
        $this->resetPage();
    }

    public function updatedLevel(): void
    {
        $this->resetPage();
    }

    public function updatedStatus(): void
    {
        $this->resetPage();
    }

    public ?string $level = 'all';

    public ?string $status = 'all';

    public bool $modalOpen = false;

    public string $mode = 'create';

    public ?int $articleId = null;

    public string $title = '';

    public string $slug = '';

    public string $excerpt = '';

    public string $content = '';

    public string $statusField = 'draft';

    public ?int $category_id = null;

    public string $levelField = 'sma';

    public string $featured_image = '';

    public ?string $published_at = null;

    /** @var array<int, int> */
    public array $selectedTags = [];

    /** @var array<int, int> */
    public array $selectedGlossaries = [];

    public array $citations = [];

    public array $questions = [];

    public ?UploadedFile $featuredImageUpload = null;

    public function boot(): void
    {
        abort_unless(auth()->user()?->isAdmin() || auth()->user()?->isPenulis(), 403);
    }

    public function updatedTitle(string $value): void
    {
        if ($this->mode === 'create') {
            $this->slug = Str::slug($value);
        }
    }

    public function openCreateModal(): void
    {
        $this->resetForm();
        $this->mode = 'create';

        $firstCat = Category::query()->first();
        if ($firstCat instanceof Category) {
            $this->category_id = $firstCat->id;
            $this->levelField = $firstCat->level;
        }

        $this->modalOpen = true;
    }

    public function editArticle(int $id): void
    {
        $article = Article::query()->findOrFail($id);
        Gate::authorize('update', $article);

        $this->articleId = $article->id;
        $this->mode = 'edit';
        $this->title = $article->title;
        $this->slug = $article->slug;
        $this->excerpt = $article->excerpt ?? '';
        $this->content = $article->content;
        $this->statusField = $article->status;
        $this->category_id = $article->category_id;
        $this->levelField = $article->level;
        $this->featured_image = $article->featured_image ?? '';
        $this->published_at = $article->published_at ? $article->published_at->format('Y-m-d\TH:i') : null;
        $this->selectedTags = $article->tags->pluck('id')->toArray();
        $this->selectedGlossaries = $article->glossaries->pluck('id')->toArray();
        $this->citations = $article->citations
            ->map(fn (Citation $citation) => [
                'citation_text' => $citation->citation_text,
                'doi' => $citation->doi ?? '',
                'url' => $citation->url ?? '',
            ])->all();
        $this->questions = $article->quizQuestions
            ->map(fn (QuizQuestion $question) => [
                'question' => $question->question,
                'options' => $question->options,
                'correct_option_index' => $question->correct_option_index,
                'explanation' => $question->explanation,
            ])->all();
        $this->modalOpen = true;
    }

    public function updatedCategoryId(?int $value): void
    {
        $category = Category::query()->find($value);
        if ($category) {
            $this->levelField = $category->level;
        }
    }

    public function saveArticle(): void
    {
        $allowedStatuses = auth()->user()?->isAdmin()
            ? ['draft', 'published', 'archived']
            : ['draft', 'revision_requested', 'rejected'];

        $rules = [
            'title' => 'required|string|max:255',
            'slug' => 'required|string|max:255|unique:articles,slug,'.($this->articleId ?? 'NULL').',id',
            'excerpt' => 'nullable|string|max:1000',
            'content' => 'required|string',
            'statusField' => ['required', Rule::in($allowedStatuses)],
            'category_id' => 'required|exists:categories,id',
            'levelField' => 'required|in:sma,pt',
            'featured_image' => 'nullable|url|max:2048',
            'published_at' => 'nullable|date',
            'selectedTags' => ['array'],
            'selectedTags.*' => ['integer', 'exists:tags,id'],
            'selectedGlossaries' => ['array'],
            'selectedGlossaries.*' => ['integer', 'exists:glossaries,id'],
            'citations' => ['array'],
            'citations.*.citation_text' => ['required', 'string', 'max:2000'],
            'citations.*.doi' => ['nullable', 'string', 'max:255'],
            'citations.*.url' => ['nullable', 'url', 'max:2048'],
            'questions' => ['array'],
            'questions.*.question' => ['required', 'string', 'max:2000'],
            'questions.*.options' => ['required', 'array', 'min:2', 'max:6'],
            'questions.*.options.*' => ['required', 'string', 'max:500'],
            'questions.*.correct_option_index' => ['required', 'integer', 'min:0'],
            'questions.*.explanation' => ['required', 'string', 'max:2000'],
        ];

        $this->validate($rules);

        $pubDate = $this->published_at ? Carbon::parse($this->published_at) : null;
        if ($this->statusField === 'published' && ! $pubDate) {
            $pubDate = now();
        }

        $payload = [
            'title' => $this->title,
            'slug' => $this->slug,
            'excerpt' => $this->excerpt ?: null,
            'content' => app(ArticleHtmlSanitizer::class)->sanitize($this->content),
            'status' => $this->statusField,
            'category_id' => $this->category_id,
            'level' => $this->levelField,
            'featured_image' => $this->featured_image ?: null,
            'published_at' => $pubDate,
        ];

        $article = null;

        DB::transaction(function () use ($payload, &$article): void {
            if ($this->mode === 'edit' && $this->articleId) {
                $article = Article::query()->findOrFail($this->articleId);
                Gate::authorize('update', $article);
                $article->update($payload);
            } else {
                $payload['user_id'] = auth()->id();
                $article = Article::query()->create($payload);
            }

            $article->tags()->sync($this->selectedTags);
            $article->glossaries()->sync($this->selectedGlossaries);

            $article->citations()->delete();
            if ($article->level === 'pt') {
                foreach ($this->citations as $index => $citation) {
                    $article->citations()->create([
                        ...$citation,
                        'doi' => $citation['doi'] ?: null,
                        'url' => $citation['url'] ?: null,
                        'sort_order' => $index + 1,
                    ]);
                }
            }

            $article->quizQuestions()->delete();
            foreach ($this->questions as $index => $question) {
                if ($question['correct_option_index'] >= count($question['options'])) {
                    throw ValidationException::withMessages([
                        "questions.{$index}.correct_option_index" => 'Jawaban benar tidak tersedia pada daftar opsi.',
                    ]);
                }

                $article->quizQuestions()->create([
                    ...$question,
                    'sort_order' => $index + 1,
                ]);
            }
        });

        $this->resetForm();
        $this->modalOpen = false;

        $this->dispatch('notify', message: 'Artikel berhasil disimpan.');
    }

    public function deleteArticle(int $id): void
    {
        $article = Article::query()->findOrFail($id);
        Gate::authorize('delete', $article);
        $article->delete();
        $this->dispatch('notify', message: 'Artikel berhasil dihapus.');
    }

    public function submitForReview(int $id): void
    {
        $article = Article::query()->findOrFail($id);
        Gate::authorize('submitForReview', $article);

        if ($article->level === 'pt' && ! $article->citations()->exists()) {
            $this->addError('review', 'Artikel PT wajib memiliki minimal satu sitasi.');

            return;
        }

        if (! $article->quizQuestions()->exists()) {
            $this->addError('review', 'Artikel wajib memiliki minimal satu soal kuis.');

            return;
        }

        $article->update([
            'status' => 'pending_review',
            'published_at' => null,
        ]);

        $this->dispatch('notify', message: 'Artikel dikirim ke antrean review Admin.');
    }

    public function uploadFeaturedImage(): void
    {
        $this->validate([
            'featuredImageUpload' => ['required', 'image', 'mimes:jpg,jpeg,png,webp', 'max:5120', 'dimensions:max_width=6000,max_height=6000'],
        ]);

        try {
            $asset = app(WebpMediaStorage::class)->store($this->featuredImageUpload, auth()->user());
        } catch (RuntimeException) {
            $this->addError('featuredImageUpload', 'Gambar belum dapat diproses atau disimpan. Silakan coba kembali.');

            return;
        }
        $this->featured_image = $asset->url;
        $this->reset('featuredImageUpload');
    }

    public function addCitation(): void
    {
        $this->citations[] = [
            'citation_text' => '',
            'doi' => '',
            'url' => '',
        ];
    }

    public function removeCitation(int $index): void
    {
        unset($this->citations[$index]);
        $this->citations = array_values($this->citations);
    }

    public function addQuestion(): void
    {
        $this->questions[] = [
            'question' => '',
            'options' => ['', ''],
            'correct_option_index' => 0,
            'explanation' => '',
        ];
    }

    public function removeQuestion(int $index): void
    {
        unset($this->questions[$index]);
        $this->questions = array_values($this->questions);
    }

    public function addQuestionOption(int $questionIndex): void
    {
        if (count($this->questions[$questionIndex]['options'] ?? []) < 6) {
            $this->questions[$questionIndex]['options'][] = '';
        }
    }

    public function removeQuestionOption(int $questionIndex, int $optionIndex): void
    {
        if (count($this->questions[$questionIndex]['options'] ?? []) <= 2) {
            return;
        }

        unset($this->questions[$questionIndex]['options'][$optionIndex]);
        $this->questions[$questionIndex]['options'] = array_values($this->questions[$questionIndex]['options']);
        $this->questions[$questionIndex]['correct_option_index'] = 0;
    }

    public function selectMedia(string $url): void
    {
        $this->featured_image = $url;
    }

    private function resetForm(): void
    {
        $this->articleId = null;
        $this->mode = 'create';
        $this->title = '';
        $this->slug = '';
        $this->excerpt = '';
        $this->content = '';
        $this->statusField = 'draft';
        $this->category_id = null;
        $this->levelField = 'sma';
        $this->featured_image = '';
        $this->published_at = null;
        $this->selectedTags = [];
        $this->selectedGlossaries = [];
        $this->citations = [];
        $this->questions = [];
        $this->featuredImageUpload = null;
    }

    public function render(): View
    {
        $articlesQuery = Article::query()
            ->with(['category', 'author'])
            ->when(auth()->user()?->isPenulis(), fn ($query) => $query->where('user_id', auth()->id()))
            ->when($this->level !== 'all' && $this->level !== null, fn ($query) => $query->where('level', $this->level))
            ->when($this->status !== 'all' && $this->status !== null, fn ($query) => $query->where('status', $this->status))
            ->when($this->search !== '' && $this->search !== null, function ($query): void {
                $query->where(function ($query): void {
                    $query->whereLike('title', "%{$this->search}%")
                        ->orWhereLike('content', "%{$this->search}%")
                        ->orWhereLike('excerpt', "%{$this->search}%");
                });
            })
            ->orderByDesc('id')
            ->paginate(8);

        return view('admin.livewire.article-manager', [
            'articlesList' => $articlesQuery,
            'categories' => Category::query()->orderBy('name')->get(),
            'tags' => Tag::query()->orderBy('name')->get(),
            'glossaries' => Glossary::query()->orderBy('term')->get(),
            'mediaAssets' => MediaAsset::query()->latest()->limit(18)->get(),
        ]);
    }
}
