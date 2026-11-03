<?php

namespace App\Support;

use App\Models\MediaAsset;
use App\Models\User;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use RuntimeException;

class WebpMediaStorage
{
    private const MAX_WIDTH = 1920;

    public function store(UploadedFile $file, User $owner): MediaAsset
    {
        $source = imagecreatefromstring((string) file_get_contents($file->getRealPath()));

        if ($source === false) {
            throw new RuntimeException('File gambar tidak dapat diproses.');
        }

        $sourceWidth = imagesx($source);
        $sourceHeight = imagesy($source);
        $targetWidth = min($sourceWidth, self::MAX_WIDTH);
        $targetHeight = (int) round($sourceHeight * ($targetWidth / $sourceWidth));
        $target = imagecreatetruecolor($targetWidth, $targetHeight);

        imagealphablending($target, false);
        imagesavealpha($target, true);
        imagecopyresampled(
            $target,
            $source,
            0,
            0,
            0,
            0,
            $targetWidth,
            $targetHeight,
            $sourceWidth,
            $sourceHeight,
        );

        ob_start();
        imagewebp($target, null, 82);
        $webp = ob_get_clean();
        imagedestroy($source);
        imagedestroy($target);

        if (! is_string($webp) || $webp === '') {
            throw new RuntimeException('Konversi gambar ke WebP gagal.');
        }

        $path = 'media/'.now()->format('Y/m').'/'.Str::uuid().'.webp';
        if (! Storage::disk('public')->put($path, $webp)) {
            throw new RuntimeException('Gambar belum dapat disimpan. Silakan coba kembali.');
        }

        return MediaAsset::query()->create([
            'user_id' => $owner->id,
            'original_name' => $file->getClientOriginalName(),
            'disk' => 'public',
            'path' => $path,
            'mime_type' => 'image/webp',
            'width' => $targetWidth,
            'height' => $targetHeight,
            'size' => strlen($webp),
        ]);
    }
}
