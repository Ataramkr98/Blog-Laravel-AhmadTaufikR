<?php

namespace App\Livewire;

use App\Models\Article;
use Illuminate\Contracts\View\View;
use Livewire\Component;
use Livewire\WithPagination;

class ArticleModeration extends Component
{
    use WithPagination;

    public string $reviewNotes = '';

    public function approve(int $articleId): void
    {
        $this->ensureAdmin();
        $article = $this->pendingArticle($articleId);

        $article->update([
            'status' => 'published',
            'published_at' => $article->published_at ?? now(),
        ]);
        $this->recordReview($article, 'approved');
    }

    public function requestRevision(int $articleId): void
    {
        $this->ensureAdmin();
        $this->validate(['reviewNotes' => ['required', 'string', 'min:5', 'max:2000']]);
        $article = $this->pendingArticle($articleId);

        $article->update([
            'status' => 'revision_requested',
            'published_at' => null,
        ]);
        $this->recordReview($article, 'revision_requested', $this->reviewNotes);
        $this->reset('reviewNotes');
    }

    public function reject(int $articleId): void
    {
        $this->ensureAdmin();
        $this->validate(['reviewNotes' => ['required', 'string', 'min:5', 'max:2000']]);
        $article = $this->pendingArticle($articleId);

        $article->update([
            'status' => 'rejected',
            'published_at' => null,
        ]);
        $this->recordReview($article, 'rejected', $this->reviewNotes);
        $this->reset('reviewNotes');
    }

    public function archive(int $articleId): void
    {
        $this->ensureAdmin();
        $article = Article::query()
            ->whereIn('status', ['draft', 'published', 'rejected'])
            ->findOrFail($articleId);

        $article->update(['status' => 'archived']);
        $this->recordReview($article, 'archived');
    }

    public function render(): View
    {
        $this->ensureAdmin();

        return view('admin.livewire.article-moderation', [
            'articles' => Article::query()
                ->with(['author', 'category'])
                ->where('status', 'pending_review')
                ->latest('updated_at')
                ->paginate(10, pageName: 'reviewPage'),
            'archivableArticles' => Article::query()
                ->with(['author', 'category'])
                ->whereIn('status', ['draft', 'published', 'rejected'])
                ->latest('updated_at')
                ->paginate(10, pageName: 'archivePage'),
        ]);
    }

    private function ensureAdmin(): void
    {
        abort_unless(auth()->user()?->isAdmin(), 403);
    }

    private function pendingArticle(int $articleId): Article
    {
        return Article::query()
            ->where('status', 'pending_review')
            ->findOrFail($articleId);
    }

    private function recordReview(Article $article, string $action, ?string $notes = null): void
    {
        $article->reviews()->create([
            'reviewer_id' => auth()->id(),
            'action' => $action,
            'notes' => $notes,
        ]);
        $this->dispatch('notify', message: 'Status moderasi artikel berhasil diperbarui.');
    }
}
