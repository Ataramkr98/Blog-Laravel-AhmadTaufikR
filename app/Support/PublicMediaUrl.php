<?php

namespace App\Support;

use Illuminate\Support\Facades\Storage;

class PublicMediaUrl
{
    public static function resolve(?string $value): ?string
    {
        if (blank($value)) {
            return null;
        }

        $path = parse_url($value, PHP_URL_PATH);
        $host = parse_url($value, PHP_URL_HOST);
        $storagePath = is_string($path) ? ltrim($path, '/') : '';

        if (! str_starts_with($storagePath, 'storage/')) {
            return $value;
        }

        $localHosts = ['localhost', '127.0.0.1', '::1', parse_url(config('app.url'), PHP_URL_HOST), request()->getHost()];
        $relativePath = substr($storagePath, strlen('storage/'));
        $legacyLocalFile = is_string($host) && str_ends_with(strtolower($host), '.test')
            && ! str_contains($relativePath, '..') && Storage::disk('public')->exists($relativePath);

        if (! $host || in_array($host, $localHosts, true) || $legacyLocalFile) {
            return asset($storagePath);
        }

        return $value;
    }
}
