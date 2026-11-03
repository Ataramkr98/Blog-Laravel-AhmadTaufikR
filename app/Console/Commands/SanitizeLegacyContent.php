<?php

namespace App\Console\Commands;

use App\Models\Article;
use App\Support\ArticleHtmlSanitizer;
use Illuminate\Console\Command;

class SanitizeLegacyContent extends Command
{
    protected $signature = 'sanitize:legacy-content';

    protected $description = 'Re-sanitize published article content and strip non-PT citations';

    public function handle(ArticleHtmlSanitizer $sanitizer): int
    {
        $this->info('Starting legacy content sanitization...');

        $articles = Article::query()
            ->where('status', 'published')
            ->whereNotNull('published_at')
            ->get();

        $count = 0;

        foreach ($articles as $article) {
            $originalLength = mb_strlen($article->content);
            $article->content = $sanitizer->sanitize($article->content);

            if ($article->isDirty('content')) {
                $article->save();
                $count++;
                $this->line("- <comment>{$article->title}</comment>: content re-sanitized ({$originalLength} → ".mb_strlen($article->content).' chars)');
            }

            if ($article->level !== 'pt') {
                $citationsRemoved = $article->citations()->count();
                if ($citationsRemoved > 0) {
                    $article->citations()->delete();
                    $this->line("  - Removed {$citationsRemoved} non-PT citations");
                }
            }
        }

        $this->info("Sanitization complete. {$count} articles updated.");

        return self::SUCCESS;
    }
}
