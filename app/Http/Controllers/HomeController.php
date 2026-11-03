<?php

namespace App\Http\Controllers;

use App\Models\Article;
use App\Models\Course;
use App\Models\Glossary;
use App\Models\Tag;
use App\Models\User;
use Illuminate\View\View;

class HomeController extends Controller
{
    public function index(): View
    {
        $preferredLevel = auth()->user()?->academic_level;

        return view('home', [
            'popularTags' => Tag::query()->whereHas('articles', fn ($query) => $query->where('status', 'published')->whereNotNull('published_at')->where('published_at', '<=', now()))
                ->withCount(['articles' => fn ($query) => $query->where('status', 'published')->whereNotNull('published_at')->where('published_at', '<=', now())])->orderByDesc('articles_count')->orderBy('name')->limit(8)->get(),
            'articles' => Article::query()
                ->published()
                ->with(['category', 'author', 'tags'])
                // Surface articles matching the reader's preferred level first,
                // then fall back to recency for everyone else / guests.
                ->when($preferredLevel, fn ($query) => $query->orderByRaw('CASE WHEN level = ? THEN 0 ELSE 1 END', [$preferredLevel]))
                ->orderByDesc('published_at')
                ->limit(6)
                ->get(),
            'glossaries' => Glossary::query()
                ->orderBy('term')
                ->limit(7)
                ->get(),
        ]);
    }

    public function about(): View
    {
        return view('about', [
            'contributors' => User::query()->whereIn('role', ['admin', 'penulis'])
                ->whereHas('articles', fn ($query) => $query->where('status', 'published')->whereNotNull('published_at')->where('published_at', '<=', now()))
                ->withCount(['articles' => fn ($query) => $query->where('status', 'published')->whereNotNull('published_at')->where('published_at', '<=', now())])
                ->orderByDesc('articles_count')->orderBy('name')->get(),
            'articleCount' => Article::query()->published()->count(),
            'courseCount' => Course::query()->published()->count(),
            'glossaryCount' => Glossary::query()->count(),
        ]);
    }
}
