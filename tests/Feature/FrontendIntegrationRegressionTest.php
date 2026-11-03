<?php

use App\Livewire\AdminCourseManager;
use App\Livewire\AdminUserManager;
use App\Models\Article;
use App\Models\Course;
use App\Models\MediaAsset;
use App\Models\Tag;
use App\Models\User;
use App\Support\PublicMediaUrl;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Livewire\Livewire;

test('legacy development media follows the current origin only when its local file exists', function () {
    Storage::fake('public');
    Storage::disk('public')->put('articles/seed/cover.webp', 'image-fixture');
    app('url')->setRequest(Request::create('https://portfolio.example.com'));

    expect(PublicMediaUrl::resolve('http://old-project.test/storage/articles/seed/cover.webp'))
        ->toBe('https://portfolio.example.com/storage/articles/seed/cover.webp')
        ->and(PublicMediaUrl::resolve('https://cdn.example.com/storage/articles/seed/cover.webp'))
        ->toBe('https://cdn.example.com/storage/articles/seed/cover.webp')
        ->and(PublicMediaUrl::resolve('https://other-project.test/storage/missing.webp'))
        ->toBe('https://other-project.test/storage/missing.webp');
});

test('local article course and avatar images follow the current site origin', function () {
    config(['app.url' => 'http://localhost:8000', 'filesystems.disks.public.url' => 'http://localhost:8000/storage']);
    $author = User::factory()->create(['avatar_url' => 'http://localhost:8000/storage/users/author.webp']);
    $article = Article::factory()->for($author, 'author')->create(['featured_image' => 'http://localhost:8000/storage/articles/cover.webp']);
    $course = Course::factory()->published()->create(['featured_image' => '/storage/courses/cover.webp']);

    $this->get('http://recruiter.test/articles/'.$article->slug)->assertOk()
        ->assertSee('http://recruiter.test/storage/users/author.webp', false);
    $this->get('http://recruiter.test/courses')->assertOk()
        ->assertSee('http://recruiter.test/storage/courses/cover.webp', false);
    $this->get('http://recruiter.test/')->assertOk()
        ->assertSee('http://recruiter.test/storage/articles/cover.webp', false);
});

test('external media urls remain unchanged', function () {
    $url = 'https://cdn.example.com/storage/articles/custom-cover.webp';
    $article = Article::factory()->create(['featured_image' => $url]);
    $author = User::factory()->create(['avatar_url' => 'https://images.example.com/avatar.webp']);

    expect($article->featured_image)->toBe($url)
        ->and($author->avatar_url)->toBe('https://images.example.com/avatar.webp');
});

test('public media library urls are resolved from the active origin', function () {
    $asset = MediaAsset::query()->create(['disk' => 'public', 'path' => 'media/cell.webp', 'original_name' => 'cell.webp', 'mime_type' => 'image/webp', 'width' => 100, 'height' => 100, 'size' => 100]);
    $request = Request::create('https://portfolio.test/admin/articles');
    app('url')->setRequest($request);

    expect($asset->url)->toBe('https://portfolio.test/storage/media/cell.webp');
});

test('about page shows actual contributors instead of fabricated academic profiles', function () {
    $writer = User::factory()->create(['name' => 'Ratna Peneliti', 'role' => 'penulis']);
    Article::factory()->for($writer, 'author')->create();

    $this->get(route('about'))->assertOk()->assertSee('Ratna Peneliti')
        ->assertDontSee('Harvard University Alumni')->assertDontSee('Prof. Amelia Hart');
});

test('popular topic links come from tags on published articles', function () {
    $article = Article::factory()->create();
    $tag = Tag::factory()->create(['name' => 'Mikrobioma Terapan']);
    $article->tags()->attach($tag);

    $this->get(route('home'))->assertOk()->assertSee('Mikrobioma Terapan');
    $this->get(route('articles.index'))->assertOk()->assertSee('Mikrobioma Terapan');
});

test('course editor can add and remove unsaved modules lessons questions and options', function () {
    $admin = User::factory()->create(['role' => 'admin']);
    $editor = Livewire::actingAs($admin)->test(AdminCourseManager::class)
        ->call('addModule')->call('removeModule', 1)
        ->call('addLesson', 0)->call('removeLesson', 0, 1)
        ->call('addExamQuestion')->call('removeExamQuestion', 1)
        ->call('addExamOption', 0)->call('removeExamOption', 0, 2);

    expect($editor->get('modules'))->toHaveCount(1)
        ->and($editor->get('modules')[0]['lessons'])->toHaveCount(1)
        ->and($editor->get('examQuestions'))->toHaveCount(1)
        ->and($editor->get('examQuestions')[0]['options'])->toHaveCount(2);
});

test('admin article search uses the header search query parameter', function () {
    $admin = User::factory()->create(['role' => 'admin']);
    Article::factory()->create(['title' => 'Pembahasan Mitokondria Terapan']);
    Article::factory()->create(['title' => 'Artikel lain yang tidak cocok']);

    $this->actingAs($admin)->get(route('admin.articles', ['search' => 'Mitokondria']))
        ->assertOk()->assertSee('Pembahasan Mitokondria Terapan')->assertDontSee('Artikel lain yang tidak cocok');
});

test('registration rejects a different password confirmation when supplied', function () {
    $this->postJson(route('register'), [
        'name' => 'Dina Pramesti', 'email' => 'dina@example.test',
        'password' => 'SainsBiologi2026!', 'password_confirmation' => 'PasswordBerbeda2026!', 'academic_level' => 'sma',
    ])->assertUnprocessable()->assertJsonValidationErrors('password_confirmation');
    $this->assertDatabaseMissing('users', ['email' => 'dina@example.test']);
});

test('removing an earlier exam option keeps the correct answer attached to the same text', function () {
    $admin = User::factory()->create(['role' => 'admin']);
    $editor = Livewire::actingAs($admin)->test(AdminCourseManager::class)
        ->set('examQuestions.0.options', ['Sel', 'Inti', 'Membran', 'Sitoplasma'])
        ->set('examQuestions.0.correct_option_index', 2)
        ->call('removeExamOption', 0, 0);

    expect($editor->get('examQuestions')[0]['options'])->toBe(['Inti', 'Membran', 'Sitoplasma'])
        ->and($editor->get('examQuestions')[0]['correct_option_index'])->toBe(1);
});

test('admin filtering resets the page so matching rows are visible', function () {
    $admin = User::factory()->create(['role' => 'admin']);
    User::factory()->count(13)->create();
    User::factory()->create(['name' => 'Ratna Prameswari']);
    Livewire::actingAs($admin)->test(AdminUserManager::class)
        ->call('setPage', 2)->set('search', 'Ratna Prameswari')
        ->assertSet('paginators.page', 1)->assertSee('Ratna Prameswari');

    Livewire::actingAs($admin)->test(AdminArticleManager::class)
        ->call('setPage', 2)->set('status', 'published')->assertSet('paginators.page', 1);
});

test('removing the correct exam option requires choosing a new answer explicitly', function () {
    $admin = User::factory()->create(['role' => 'admin']);
    $editor = Livewire::actingAs($admin)->test(AdminCourseManager::class)
        ->set('examQuestions.0.options', ['Inti', 'Membran', 'Sitoplasma'])
        ->set('examQuestions.0.correct_option_index', 1)->call('removeExamOption', 0, 1);

    expect($editor->get('examQuestions')[0]['correct_option_index'])->toBeNull();
    $editor->call('saveCourse')->assertHasErrors(['examQuestions.0.correct_option_index' => 'required']);
});

test('removing an option preserves the unanswered placeholder selection', function () {
    $admin = User::factory()->create(['role' => 'admin']);
    $editor = Livewire::actingAs($admin)->test(AdminCourseManager::class)
        ->set('examQuestions.0.options', ['Inti', 'Membran', 'Sitoplasma'])
        ->set('examQuestions.0.correct_option_index', '')->call('removeExamOption', 0, 2);

    expect($editor->get('examQuestions')[0]['correct_option_index'])->toBeNull();
});
