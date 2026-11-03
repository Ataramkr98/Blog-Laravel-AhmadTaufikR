<?php

use App\Livewire\AdminArticleManager;
use App\Livewire\ArticleModeration;
use App\Models\Article;
use App\Models\ContentReview;
use App\Models\User;
use Livewire\Livewire;

test('writer cannot publish an article directly from the editor', function () {
    $writer = User::factory()->create(['role' => 'penulis']);
    $article = Article::factory()->draft()->for($writer, 'author')->create();

    Livewire::actingAs($writer)
        ->test(AdminArticleManager::class)
        ->call('editArticle', $article->id)
        ->set('statusField', 'published')
        ->call('saveArticle')
        ->assertHasErrors(['statusField']);

    expect($article->fresh()->status)->toBe('draft');
});

test('administrator approves a submitted article and records the review', function () {
    $admin = User::factory()->create(['role' => 'admin']);
    $article = Article::factory()->for(User::factory()->state(['role' => 'penulis']), 'author')->create([
        'status' => 'pending_review',
        'published_at' => null,
    ]);

    Livewire::actingAs($admin)
        ->test(ArticleModeration::class)
        ->call('approve', $article->id);

    expect($article->fresh())
        ->status->toBe('published')
        ->published_at->not->toBeNull();

    expect(ContentReview::query()
        ->where('reviewable_type', Article::class)
        ->where('reviewable_id', $article->id)
        ->where('action', 'approved')
        ->exists())->toBeTrue();
});

test('administrator must provide notes when requesting revision', function () {
    $admin = User::factory()->create(['role' => 'admin']);
    $article = Article::factory()->create([
        'status' => 'pending_review',
        'published_at' => null,
    ]);

    Livewire::actingAs($admin)
        ->test(ArticleModeration::class)
        ->call('requestRevision', $article->id)
        ->assertHasErrors(['reviewNotes']);

    expect($article->fresh()->status)->toBe('pending_review');
});
