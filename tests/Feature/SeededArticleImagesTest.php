<?php

use App\Models\Article;
use App\Models\MediaAsset;
use App\Models\User;
use Database\Seeders\ContentSeeder;
use Illuminate\Support\Facades\Storage;

test('content seeder publishes deterministic webp covers for every article', function () {
    Storage::fake('public');

    $this->seed(ContentSeeder::class);

    $articles = Article::query()->orderBy('slug')->get();

    expect($articles)->toHaveCount(12);

    foreach ($articles as $article) {
        $path = 'articles/seed/'.$article->slug.'.webp';
        $imageInfo = getimagesize(Storage::disk('public')->path($path));

        Storage::disk('public')->assertExists($path);

        expect($article->featured_image)
            ->toBe(asset('storage/'.$path))
            ->and($imageInfo)->not->toBeFalse()
            ->and($imageInfo[0])->toBe(1200)
            ->and($imageInfo[1])->toBe(630)
            ->and($imageInfo[2])->toBe(IMAGETYPE_WEBP);

        $this->assertDatabaseHas('media_assets', [
            'path' => $path,
            'disk' => 'public',
            'mime_type' => 'image/webp',
            'width' => 1200,
            'height' => 630,
            'size' => Storage::disk('public')->size($path),
        ]);
    }

    $this->seed(ContentSeeder::class);

    expect(MediaAsset::query()->where('path', 'like', 'articles/seed/%')->count())->toBe(12)
        ->and(Storage::disk('public')->files('articles/seed'))->toHaveCount(12);
});

test('content seeder replaces legacy covers while preserving custom images', function () {
    Storage::fake('public');

    $this->seed(ContentSeeder::class);

    [$legacy, $uploaded, $external] = Article::query()->orderBy('slug')->limit(3)->get()->all();
    $uploadedUrl = Storage::disk('public')->url('media/custom-cover.webp');
    $externalUrl = 'https://cdn.example.com/storage/articles/custom-cover.webp';

    $legacy->update(['featured_image' => '/storage/articles/2026/07/legacy-cover.webp']);
    $uploaded->update(['featured_image' => $uploadedUrl]);
    $external->update(['featured_image' => $externalUrl]);

    $this->seed(ContentSeeder::class);

    expect($legacy->fresh()->featured_image)
        ->toBe(asset('storage/articles/seed/'.$legacy->slug.'.webp'))
        ->and($uploaded->fresh()->getRawOriginal('featured_image'))->toBe($uploadedUrl)
        ->and($external->fresh()->featured_image)->toBe($externalUrl);
});

test('content seeder publishes one deterministic webp avatar for every seeded article author', function () {
    Storage::fake('public');

    $this->seed(ContentSeeder::class);

    $path = 'users/seed/dr-sarah-stone.webp';
    $imageInfo = getimagesize(Storage::disk('public')->path($path));
    $admin = User::query()->where('email', 'admin@biology.learn')->firstOrFail();
    $articles = Article::query()->with('author')->get();

    Storage::disk('public')->assertExists($path);

    expect($articles)->toHaveCount(12)
        ->and($articles->every(fn (Article $article) => $article->user_id === $admin->id))->toBeTrue()
        ->and($articles->every(fn (Article $article) => $article->author?->avatar_url === $admin->avatar_url))->toBeTrue()
        ->and($admin->avatar_url)->toBe(asset('storage/'.$path))
        ->and($imageInfo)->not->toBeFalse()
        ->and($imageInfo[0])->toBe(512)
        ->and($imageInfo[1])->toBe(512)
        ->and($imageInfo[2])->toBe(IMAGETYPE_WEBP);

    $this->assertDatabaseHas('media_assets', [
        'user_id' => $admin->id,
        'path' => $path,
        'disk' => 'public',
        'mime_type' => 'image/webp',
        'width' => 512,
        'height' => 512,
        'size' => Storage::disk('public')->size($path),
    ]);

    $this->seed(ContentSeeder::class);

    expect(MediaAsset::query()->where('path', 'like', 'users/seed/%')->count())->toBe(1)
        ->and(Storage::disk('public')->files('users/seed'))->toHaveCount(1);
});

test('content seeder preserves custom local and external user avatars', function () {
    Storage::fake('public');

    $this->seed(ContentSeeder::class);

    $admin = User::query()->where('email', 'admin@biology.learn')->firstOrFail();
    $customLocalUrl = Storage::disk('public')->url('media/custom-avatar.webp');
    $admin->update(['avatar_url' => $customLocalUrl]);

    $this->seed(ContentSeeder::class);

    expect($admin->fresh()->getRawOriginal('avatar_url'))->toBe($customLocalUrl);

    $customExternalUrl = 'https://cdn.example.com/avatars/custom-avatar.webp';
    $admin->update(['avatar_url' => $customExternalUrl]);

    $this->seed(ContentSeeder::class);

    expect($admin->fresh()->avatar_url)->toBe($customExternalUrl);
});
