<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class QuizQuestion extends Model
{
    use HasFactory;

    protected $fillable = [
        'article_id',
        'question',
        'options',
        'correct_option_index',
        'explanation',
        'sort_order',
    ];

    protected $casts = [
        'options' => 'array',
        'correct_option_index' => 'integer',
        'sort_order' => 'integer',
    ];

    public function article(): BelongsTo
    {
        return $this->belongsTo(Article::class);
    }
}
