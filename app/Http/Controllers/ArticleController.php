<?php

namespace App\Http\Controllers;

use App\Models\Article;
use App\Models\ArticleView;
use App\Models\Tag;
use App\Support\GlossaryHighlighter;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class ArticleController extends Controller
{
    public function index(Request $request): View
    {
        $this->validateFilters($request);

        return view('articles.index', [
            'popularTags' => Tag::query()->whereHas('articles', fn ($query) => $query->where('status', 'published')->whereNotNull('published_at')->where('published_at', '<=', now()))
                ->withCount(['articles' => fn ($query) => $query->where('status', 'published')->whereNotNull('published_at')->where('published_at', '<=', now())])->orderByDesc('articles_count')->orderBy('name')->limit(8)->get(),
        ]);
    }

    public function learn(Request $request): View
    {
        $this->validateFilters($request);

        return view('learn');
    }

    private function validateFilters(Request $request): void
    {
        $request->validate([
            'search' => ['nullable', 'string', 'max:200'],
            'level' => ['nullable', 'string', Rule::in(['all', 'sma', 'pt'])],
            'category' => ['nullable', 'string', 'max:255'],
            'perPage' => ['nullable', 'integer', Rule::in([4, 6, 9])],
            'page' => ['nullable', 'integer', 'min:1'],
        ]);
    }

    public function show(Request $request, Article $article): View
    {
        abort_unless($article->isPublished(), 404);

        // The headline counts loads; article_views deduplicates analytics per visitor/day.
        $article->increment('view_count');

        // Deduplicated analytics row: one hit per article/visitor/day.
        // InsertOrIgnore respects the unique constraint so repeats are silent.
        ArticleView::insertOrIgnore([
            'article_id' => $article->id,
            'visitor_hash' => ArticleView::hashForRequest($request),
            'viewed_on' => today()->toDateString(),
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $article->load(['category', 'author', 'tags', 'citations', 'quizQuestions', 'glossaries']);
        $highlightedContent = app(GlossaryHighlighter::class)
            ->highlight($article->content, $article->glossaries);

        $relatedArticles = Article::query()
            ->published()
            ->where('id', '!=', $article->id)
            ->whereHas('category', fn ($query) => $query->whereKey($article->category_id))
            ->with(['category', 'author'])
            ->orderByDesc('published_at')
            ->limit(2)
            ->get();

        return view('articles.show', compact('article', 'relatedArticles', 'highlightedContent'));
    }
}
