<?php

use App\Livewire\ArticlesFilter;
use App\Models\Article;
use App\Models\Category;
use App\Models\Citation;
use App\Models\Glossary;
use App\Models\User;
use Livewire\Livewire;

test('article search matches linked glossary terms and common names', function () {
    $matching = Article::factory()->create(['title' => 'Protein Membran']);
    $other = Article::factory()->create(['title' => 'Ekologi Hutan']);
    $glossary = Glossary::factory()->create([
        'term' => 'Na+/K+-ATPase',
        'common_name' => 'Pompa Natrium Kalium',
    ]);
    $matching->glossaries()->attach($glossary);

    Livewire::test(ArticlesFilter::class)
        ->set('search', 'Pompa Natrium')
        ->assertSee($matching->title)
        ->assertDontSee($other->title);
});

test('linked glossary terms are interactive in the article body', function () {
    $article = Article::factory()->create([
        'content' => '<p>Pompa Na+/K+-ATPase mempertahankan gradien ion.</p>',
    ]);
    $glossary = Glossary::factory()->create([
        'term' => 'Na+/K+-ATPase',
        'definition' => 'Protein transmembran yang menggunakan ATP.',
    ]);
    $article->glossaries()->attach($glossary);

    $this->get(route('articles.show', $article))
        ->assertOk()
        ->assertSee('data-glossary-term', false)
        ->assertSee($glossary->definition);
});

test('citations render only for university articles and expose DOI and URL links', function () {
    $sma = Article::factory()->create(['level' => 'sma']);
    $ptCategory = Category::factory()->create(['level' => 'pt']);
    $pt = Article::factory()->for($ptCategory)->create(['level' => 'pt']);

    foreach ([$sma, $pt] as $article) {
        Citation::factory()->for($article)->create([
            'citation_text' => 'Referensi akademik',
            'doi' => '10.1000/example',
            'url' => 'https://example.org/reference',
        ]);
    }

    $this->get(route('articles.show', $sma))
        ->assertDontSee('Referensi akademik');

    $this->get(route('articles.show', $pt))
        ->assertSee('Referensi akademik')
        ->assertSee('https://doi.org/10.1000/example', false)
        ->assertSee('https://example.org/reference', false);
});

test('article table of contents targets existing sections', function () {
    $article = Article::factory()->create();

    $this->get(route('articles.show', $article))
        ->assertOk()
        ->assertDontSee('href="#pengantar"', false)
        ->assertSee('href="#isi-artikel"', false)
        ->assertSee('href="#kuis"', false)
        ->assertSee('id="kuis"', false);
});

test('author profile photos render across public article surfaces', function () {
    $avatarUrl = 'https://cdn.example.test/avatars/sarah-stone.webp';
    $author = User::factory()->create([
        'name' => 'Dr. Sarah Stone',
        'avatar_url' => $avatarUrl,
    ]);
    $article = Article::factory()->for($author, 'author')->create();

    foreach ([route('home'), route('articles.index'), route('articles.show', $article)] as $url) {
        $this->get($url)
            ->assertOk()
            ->assertSee('src="'.$avatarUrl.'"', false)
            ->assertSee('alt="Foto profil Dr. Sarah Stone"', false);
    }
});

test('author avatar falls back to user initials when no photo is available', function () {
    $author = User::factory()->create([
        'name' => 'Sarah Stone',
        'avatar_url' => null,
    ]);
    Article::factory()->for($author, 'author')->create();

    $this->get(route('articles.index'))
        ->assertOk()
        ->assertSee('aria-hidden="true"', false)
        ->assertSee('SS');
});
