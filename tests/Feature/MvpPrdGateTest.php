<?php

use App\Livewire\ArticleQuiz;
use App\Models\Article;
use App\Models\Citation;
use App\Models\QuizQuestion;
use App\Models\SystemSetting;
use App\Models\User;
use Database\Seeders\ContentSeeder;
use Illuminate\Support\Facades\Cache;
use Livewire\Livewire;

test('system settings persist and maintenance mode blocks public traffic while allowing admins', function () {
    $admin = User::factory()->create(['role' => 'admin']);

    $this->actingAs($admin)
        ->put(route('admin.settings.update'), [
            'maintenanceMode' => '1',
            'reviewNotifications' => '0',
            'glossaryCacheMinutes' => '15',
        ])
        ->assertRedirect();

    $setting = SystemSetting::query()->first();

    expect($setting)
        ->maintenance_mode->toBeTrue()
        ->review_notifications->toBeFalse()
        ->glossary_cache_minutes->toBe(15);

    auth()->logout();

    $this->get(route('home'))->assertStatus(503);

    $this->actingAs($admin)
        ->get(route('admin.dashboard'))
        ->assertOk();
});

test('moderation badge uses pending review count and respects notification setting', function () {
    $admin = User::factory()->create(['role' => 'admin']);

    Article::factory()->count(3)->create([
        'status' => 'pending_review',
        'published_at' => null,
    ]);

    SystemSetting::query()->first()->update(['review_notifications' => true]);
    Cache::flush();

    $this->actingAs($admin)
        ->get(route('admin.dashboard'))
        ->assertOk()
        ->assertSee('data-testid="moderation-badge"', false)
        ->assertSee('>3</span>', false);

    SystemSetting::query()->first()->update(['review_notifications' => false]);
    Cache::flush();

    $this->actingAs($admin)
        ->get(route('admin.dashboard'))
        ->assertOk()
        ->assertDontSee('data-testid="moderation-badge"', false);
});

test('article quiz shows immediate score and explanation after submit', function () {
    $article = Article::factory()->create();

    QuizQuestion::factory()->for($article)->create([
        'question' => 'Organel apa yang menghasilkan ATP?',
        'options' => ['Mitokondria', 'Ribosom'],
        'correct_option_index' => 0,
        'explanation' => 'Mitokondria menghasilkan ATP melalui respirasi seluler.',
        'sort_order' => 1,
    ]);

    Livewire::test(ArticleQuiz::class, ['articleId' => $article->id])
        ->call('selectAnswer', 0)
        ->call('submitQuiz')
        ->assertSee('1/1')
        ->assertSee('100% Benar')
        ->assertSee('Mitokondria menghasilkan ATP melalui respirasi seluler.');
});

test('content seeder keeps citations for university articles only and links glossary examples', function () {
    $this->seed(ContentSeeder::class);

    $smaCitationCount = Citation::query()
        ->whereHas('article', fn ($query) => $query->where('level', 'sma'))
        ->count();

    $ptCitationCount = Citation::query()
        ->whereHas('article', fn ($query) => $query->where('level', 'pt'))
        ->count();

    $articlesWithGlossaries = Article::query()
        ->whereHas('glossaries')
        ->count();

    expect($smaCitationCount)->toBe(0)
        ->and($ptCitationCount)->toBeGreaterThan(0)
        ->and($articlesWithGlossaries)->toBeGreaterThan(0);
});

test('public pages do not advertise active LMS features before the full build', function () {
    $this->get(route('learn'))
        ->assertOk()
        ->assertSee('Mulai Belajar', false)
        ->assertSee('Perpustakaan Artikel', false)
        ->assertDontSee('Lanjutkan Belajar')
        ->assertDontSee('Mini-Courses')
        ->assertDontSee('Progres:');

    $this->get(route('courses'))
        ->assertOk()
        ->assertSee('Full LMS', false)
        ->assertDontSee('sertifikasi terakreditasi');

    $this->get(route('join-free'))
        ->assertOk()
        ->assertSee('Kuis Mandiri', false)
        ->assertDontSee('Accredited Certifications')
        ->assertDontSee('Expert Mentorship')
        ->assertDontSee('Structured Learning Paths');
});
