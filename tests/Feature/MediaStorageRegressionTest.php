<?php

use App\Livewire\AdminArticleManager;
use App\Models\User;
use App\Support\WebpMediaStorage;
use Illuminate\Filesystem\FilesystemAdapter;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Livewire\Livewire;

test('failed media writes never create a database asset pointing to a missing file', function () {
    $owner = User::factory()->create(['role' => 'penulis']);
    $file = UploadedFile::fake()->image('sel.jpg', 100, 100);
    $disk = Mockery::mock(FilesystemAdapter::class);
    $disk->shouldReceive('put')->once()->andReturn(false);
    Storage::shouldReceive('disk')->with('public')->once()->andReturn($disk);

    expect(fn () => app(WebpMediaStorage::class)->store($file, $owner))->toThrow(RuntimeException::class);
    $this->assertDatabaseCount('media_assets', 0);
});

test('image conversion failures are presented as upload validation errors', function () {
    Storage::fake('public');
    $owner = User::factory()->create(['role' => 'penulis']);
    $storage = Mockery::mock(WebpMediaStorage::class);
    $storage->shouldReceive('store')->once()->andThrow(new RuntimeException('Storage unavailable'));
    app()->instance(WebpMediaStorage::class, $storage);

    Livewire::actingAs($owner)->test(AdminArticleManager::class)
        ->set('featuredImageUpload', UploadedFile::fake()->image('sel.jpg', 100, 100))
        ->call('uploadFeaturedImage')->assertHasErrors('featuredImageUpload');
});
