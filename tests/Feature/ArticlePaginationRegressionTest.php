<?php

use App\Livewire\ArticlesFilter;
use App\Models\Article;
use App\Models\Category;
use Livewire\Livewire;

test('article filters paginate with livewire actions and reset when the search changes', function () {
    Article::factory()->count(10)->create(['title' => 'Pelajaran Biologi']);

    $filter = Livewire::test(ArticlesFilter::class)->set('perPage', 4)
        ->call('nextPage')->assertSet('paginators.page', 2);

    expect($filter->viewData('articles')->currentPage())->toBe(2)
        ->and($filter->viewData('articles')->count())->toBe(4);

    $filter->set('search', 'Pelajaran')->assertSet('paginators.page', 1);
});

test('switching article levels clears an incompatible category', function () {
    $category = Category::factory()->create(['level' => 'sma']);

    Livewire::test(ArticlesFilter::class)
        ->set('category', $category->slug)->set('level', 'pt')->assertSet('category', 'all');
});

test('malformed filter queries return validation errors instead of runtime failures', function (string $route) {
    $this->getJson(route($route, ['level' => ['pt']]))->assertUnprocessable()->assertJsonValidationErrors('level');
})->with(['articles.index', 'learn']);
