<?php

use App\Livewire\ArticlesFilter;
use App\Models\Article;
use App\Models\Tag;
use Livewire\Livewire;

test('public and admin layouts do not load a second alpine runtime', function () {
    $public = $this->get('/');

    $public->assertOk()
        ->assertDontSee('cdn.jsdelivr.net/npm/alpinejs', false)
        ->assertDontSee('cdn.jsdelivr.net/npm/@alpinejs/persist', false);
});

test('article search matches article tags', function () {
    $matching = Article::factory()->create([
        'title' => 'Transport Aktif Membran Sel',
        'content' => 'Materi fisiologi sel.',
    ]);
    $other = Article::factory()->create([
        'title' => 'Ekologi Terumbu Karang',
        'content' => 'Materi ekologi.',
    ]);

    $tag = Tag::factory()->create(['name' => 'Mitosis', 'slug' => 'mitosis']);
    $matching->tags()->attach($tag);

    Livewire::test(ArticlesFilter::class)
        ->set('search', 'Mitosis')
        ->assertSee($matching->title)
        ->assertDontSee($other->title);
});
