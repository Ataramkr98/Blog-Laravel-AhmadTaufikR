<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Article;
use App\Models\ArticleView;
use App\Models\Glossary;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\View\View;

class DashboardController extends Controller
{
    public function index(Request $request): View
    {
        $totalArticles = Article::query()->count();
        $totalViews = Article::query()->sum('view_count');

        // Moderation queue: articles submitted for review (not drafts still being edited)
        $moderationQueueCount = Article::query()->where('status', 'pending_review')->count();
        $totalGlossaryTerms = Glossary::query()->count();

        // SMA / PT article breakdown
        $smaCount = Article::query()->where('level', 'sma')->count();
        $ptCount = Article::query()->where('level', 'pt')->count();

        // View trends from article_views (deduplicated per visitor/day)
        $range = $request->integer('range', 7); // default 7 days
        $range = in_array($range, [1, 7, 30], true) ? $range : 7;
        $since = Carbon::today()->subDays($range);

        $viewsByDate = ArticleView::query()
            ->selectRaw('viewed_on, COUNT(*) as views')
            ->where('viewed_on', '>=', $since->toDateString())
            ->groupBy('viewed_on')
            ->orderBy('viewed_on')
            ->get()
            ->keyBy('viewed_on');

        // Build a date-indexed array for the chart, filling gaps with 0
        $dailyViews = collect();
        for ($d = 0; $d < $range; $d++) {
            $date = Carbon::today()->subDays($range - 1 - $d)->toDateString();
            $dailyViews->push([
                'date' => $date,
                'views' => $viewsByDate[$date]->views ?? 0,
            ]);
        }

        // SMA vs PT views over the same range (join through articles)
        $levelViewsByDate = ArticleView::query()
            ->selectRaw('article_views.viewed_on, articles.level, COUNT(*) as views')
            ->join('articles', 'article_views.article_id', '=', 'articles.id')
            ->where('article_views.viewed_on', '>=', $since->toDateString())
            ->groupBy('article_views.viewed_on', 'articles.level')
            ->orderBy('article_views.viewed_on')
            ->get()
            ->groupBy(fn ($row) => $row->viewed_on);

        $dailySmaViews = collect();
        $dailyPtViews = collect();
        for ($d = 0; $d < $range; $d++) {
            $date = Carbon::today()->subDays($range - 1 - $d)->toDateString();
            $dayBreakdown = $levelViewsByDate[$date] ?? collect();
            $dailySmaViews->push(optional($dayBreakdown->firstWhere('level', 'sma'))->views ?? 0);
            $dailyPtViews->push(optional($dayBreakdown->firstWhere('level', 'pt'))->views ?? 0);
        }

        // 5 most recently updated articles as activity feed
        $recentActivities = Article::query()
            ->with(['author'])
            ->orderByDesc('updated_at')
            ->limit(5)
            ->get();

        // Moderation queue table (pending_review, not draft)
        $moderationQueue = Article::query()
            ->with(['category', 'author'])
            ->where('status', 'pending_review')
            ->orderByDesc('created_at')
            ->limit(5)
            ->get();

        return view('admin.dashboard', compact(
            'totalArticles',
            'totalViews',
            'moderationQueueCount',
            'totalGlossaryTerms',
            'smaCount',
            'ptCount',
            'range',
            'dailyViews',
            'dailySmaViews',
            'dailyPtViews',
            'recentActivities',
            'moderationQueue'
        ));
    }
}
