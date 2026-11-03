<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Http\Request;

/**
 * @property int $id
 * @property int $article_id
 * @property string $visitor_hash
 * @property string $viewed_on
 */
class ArticleView extends Model
{
    /**
     * Build a non-reversible, non-PII visitor hash from the request.
     * Uses HMAC-SHA256 with an app-key salt so the raw IP cannot be recovered.
     */
    public static function hashForRequest(Request $request): string
    {
        $fingerprint = $request->ip().'|'.$request->userAgent();

        return hash_hmac('sha256', $fingerprint, config('app.key'));
    }

    public function article(): BelongsTo
    {
        return $this->belongsTo(Article::class);
    }
}
