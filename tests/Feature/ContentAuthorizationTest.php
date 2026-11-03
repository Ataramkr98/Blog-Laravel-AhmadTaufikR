<?php

use App\Livewire\AdminArticleManager;
use App\Models\Article;
use App\Models\QuizQuestion;
use App\Models\User;
use Livewire\Livewire;

test('writer cannot access administrator settings or moderation', function () {
    $writer = User::factory()->create(['role' => 'penulis']);

    $this->actingAs($writer)
        ->get(route('admin.settings'))
        ->assertForbidden();

    $this->actingAs($writer)
        ->get(route('admin.moderation'))
        ->assertForbidden();
});

test('writer can edit own article but cannot edit another writers article', function () {
    $writer = User::factory()->create(['role' => 'penulis']);
    $otherWriter = User::factory()->create(['role' => 'penulis']);
    $ownArticle = Article::factory()->draft()->for($writer, 'author')->create();
    $otherArticle = Article::factory()->draft()->for($otherWriter, 'author')->create();

    Livewire::actingAs($writer)
        ->test(AdminArticleManager::class)
        ->call('editArticle', $ownArticle->id)
        ->assertSet('articleId', $ownArticle->id);

    Livewire::actingAs($writer)
        ->test(AdminArticleManager::class)
        ->call('editArticle', $otherArticle->id)
        ->assertForbidden();
});

test('writer submits an owned draft for administrator review', function () {
    $writer = User::factory()->create(['role' => 'penulis']);
    $article = Article::factory()->draft()->for($writer, 'author')->create();
    QuizQuestion::factory()->for($article)->create();

    Livewire::actingAs($writer)
        ->test(AdminArticleManager::class)
        ->call('submitForReview', $article->id);

    expect($article->fresh()->status)->toBe('pending_review');
});
