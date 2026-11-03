<?php

namespace App\Policies;

use App\Models\Article;
use App\Models\User;

class ArticlePolicy
{
    public function before(User $user): ?bool
    {
        return $user->isAdmin() ? true : null;
    }

    public function update(User $user, Article $article): bool
    {
        return $user->isPenulis()
            && $article->user_id === $user->id
            && in_array($article->status, ['draft', 'revision_requested', 'rejected'], true);
    }

    public function delete(User $user, Article $article): bool
    {
        return $this->update($user, $article)
            && in_array($article->status, ['draft', 'revision_requested', 'rejected'], true);
    }

    public function submitForReview(User $user, Article $article): bool
    {
        return $this->update($user, $article)
            && in_array($article->status, ['draft', 'revision_requested', 'rejected'], true);
    }

    public function archive(User $user, Article $article): bool
    {
        return $user->isAdmin()
            && in_array($article->status, ['draft', 'published', 'rejected'], true);
    }
}
