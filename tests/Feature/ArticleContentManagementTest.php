<?php

use App\Livewire\AdminArticleManager;
use App\Models\Article;
use App\Models\Category;
use App\Models\Glossary;
use App\Models\User;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Livewire\Livewire;

test('article html is sanitized before it is stored', function () {
    $writer = User::factory()->create(['role' => 'penulis']);
    $article = Article::factory()->draft()->for($writer, 'author')->create();

    Livewire::actingAs($writer)
        ->test(AdminArticleManager::class)
        ->call('editArticle', $article->id)
        ->set('content', '<p>Konten aman</p><script>alert(1)</script><img src="javascript:alert(2)">')
        ->call('saveArticle')
        ->assertHasNoErrors();

    expect($article->fresh()->content)
        ->toContain('<p>Konten aman</p>')
        ->not->toContain('<script')
        ->not->toContain('javascript:');
});

test('article editor persists citations questions and glossary terms', function () {
    $writer = User::factory()->create(['role' => 'penulis']);
    $category = Category::factory()->create(['level' => 'pt']);
    $glossary = Glossary::factory()->create(['level' => 'pt']);
    $article = Article::factory()->draft()->for($writer, 'author')->for($category)->create(['level' => 'pt']);

    Livewire::actingAs($writer)
        ->test(AdminArticleManager::class)
        ->call('editArticle', $article->id)
        ->set('citations', [[
            'citation_text' => 'Alberts et al. Molecular Biology of the Cell.',
            'doi' => '10.1000/example',
            'url' => 'https://example.org/reference',
        ]])
        ->set('questions', [[
            'question' => 'Apa fungsi ATP?',
            'options' => ['Energi', 'Pigmen'],
            'correct_option_index' => 0,
            'explanation' => 'ATP membawa energi kimia.',
        ]])
        ->set('selectedGlossaries', [$glossary->id])
        ->call('saveArticle')
        ->assertHasNoErrors();

    expect($article->fresh()->citations)->toHaveCount(1)
        ->and($article->fresh()->quizQuestions)->toHaveCount(1)
        ->and($article->fresh()->glossaries)->toHaveCount(1);
});

test('uploaded featured image is converted to webp and registered in media library', function () {
    Storage::fake('public');
    $writer = User::factory()->create(['role' => 'penulis']);

    $component = Livewire::actingAs($writer)
        ->test(AdminArticleManager::class)
        ->set('featuredImageUpload', UploadedFile::fake()->image('cell.jpg', 1200, 630))
        ->call('uploadFeaturedImage')
        ->assertHasNoErrors(['featuredImageUpload']);

    $path = $component->get('featured_image');

    expect($path)->toEndWith('.webp');
    Storage::disk('public')->assertExists(str($path)->after('/storage/')->toString());
    $this->assertDatabaseHas('media_assets', [
        'user_id' => $writer->id,
        'mime_type' => 'image/webp',
    ]);
});
