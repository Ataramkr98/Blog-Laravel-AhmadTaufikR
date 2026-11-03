<?php

use Illuminate\Contracts\Console\Kernel;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\DB;

try {
    $root = dirname(__DIR__);

    if (! is_file($root.'/.env') && ! copy($root.'/.env.example', $root.'/.env')) {
        throw new RuntimeException('Tidak dapat membuat .env dari .env.example.');
    }

    require $root.'/vendor/autoload.php';

    $app = require $root.'/bootstrap/app.php';

    if ($app->configurationIsCached()) {
        throw new RuntimeException('Jalankan php artisan config:clear sebelum composer setup agar konfigurasi .env lokal dibaca ulang.');
    }

    $app->make(Kernel::class)->bootstrap();

    if (! $app->environment('local', 'testing')) {
        throw new RuntimeException('composer setup hanya untuk lingkungan local/testing. Gunakan langkah deployment pada README untuk server publik.');
    }

    if (blank(config('app.key'))) {
        $status = Artisan::call('key:generate', ['--force' => true]);
        fwrite(STDOUT, Artisan::output());

        if ($status !== 0) {
            exit($status);
        }
    }

    if (DB::connection()->getDriverName() === 'sqlite') {
        $database = DB::connection()->getDatabaseName();

        if ($database !== ':memory:' && ! is_file($database)) {
            if (! is_dir(dirname($database)) || ! touch($database)) {
                throw new RuntimeException('Tidak dapat membuat database SQLite. Periksa DB_DATABASE dan izin direktori database.');
            }

            fwrite(STDOUT, 'File database SQLite dibuat.'.PHP_EOL);
        }
    }

    fwrite(STDOUT, 'Konfigurasi lokal siap; key dan database yang sudah ada dipertahankan.'.PHP_EOL);
} catch (Throwable $exception) {
    // Laravel's CLI exception handler can return exit code 0 outside Artisan.
    fwrite(STDERR, 'Persiapan lokal gagal: '.$exception->getMessage().PHP_EOL);
    exit(1);
}
