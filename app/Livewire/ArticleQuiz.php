<?php

namespace App\Livewire;

use App\Models\Article;
use App\Models\QuizQuestion;
use Illuminate\Contracts\View\View;
use Livewire\Attributes\Locked;
use Livewire\Component;

class ArticleQuiz extends Component
{
    #[Locked]
    public int $articleId;

    #[Locked]
    public int $currentIndex = 0;

    /** @var array<int, int|null> */
    public array $answers = [];

    public bool $showResults = false;

    public function mount(int $articleId): void
    {
        $this->articleId = $articleId;
        $this->currentIndex = 0;
        $this->showResults = false;

        $questionsCount = QuizQuestion::where('article_id', $articleId)->count();
        $this->answers = array_fill(0, $questionsCount, null);
    }

    public function selectAnswer(int $optIndex): void
    {
        $this->answers[$this->currentIndex] = $optIndex;
    }

    public function previousQuestion(): void
    {
        if ($this->currentIndex > 0) {
            $this->currentIndex--;
        }
    }

    public function nextQuestion(): void
    {
        $questionsCount = QuizQuestion::where('article_id', $this->articleId)->count();
        if ($this->currentIndex < $questionsCount - 1) {
            $this->currentIndex++;
        }
    }

    public function submitQuiz(): void
    {
        $this->showResults = true;
    }

    public function retry(): void
    {
        $this->currentIndex = 0;
        $this->showResults = false;
        $questionsCount = QuizQuestion::where('article_id', $this->articleId)->count();
        $this->answers = array_fill(0, $questionsCount, null);
    }

    public function render(): View
    {
        abort_unless(Article::query()->findOrFail($this->articleId)->isPublished(), 404);
        $questions = QuizQuestion::where('article_id', $this->articleId)
            ->orderBy('sort_order')
            ->get();

        $totalQuestions = $questions->count();
        $currentQuestion = $questions->get($this->currentIndex);

        $score = 0;
        foreach ($questions as $qIndex => $q) {
            $userAns = $this->answers[$qIndex] ?? null;
            if ($userAns !== null && $userAns === $q->correct_option_index) {
                $score++;
            }
        }

        $percentage = $totalQuestions > 0 ? round(($score / $totalQuestions) * 100) : 0;

        return view('livewire.article-quiz', [
            'questions' => $questions,
            'currentQuestion' => $currentQuestion,
            'totalQuestions' => $totalQuestions,
            'score' => $score,
            'percentage' => $percentage,
        ]);
    }
}
