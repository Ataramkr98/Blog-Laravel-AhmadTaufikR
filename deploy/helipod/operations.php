<?php

// Run only through the CLI inside the deployment container.
use App\Models\User;
use Illuminate\Contracts\Console\Kernel;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

require dirname(__DIR__, 2).'/vendor/autoload.php';
$app = require dirname(__DIR__, 2).'/bootstrap/app.php';
$app->make(Kernel::class)->bootstrap();

$operation = $argv[1] ?? 'status';

if ($operation === 'backup-env') {
    $config = DB::connection()->getConfig();
    if ($config['driver'] !== 'pgsql') {
        throw new RuntimeException('This backup operation requires PostgreSQL.');
    }
    $variables = [
        'PGHOST' => $config['direct']['host'] ?? $config['host'],
        'PGPORT' => $config['direct']['port'] ?? $config['port'],
        'PGDATABASE' => $config['database'],
        'PGUSER' => $config['username'],
        'PGPASSWORD' => $config['password'],
        'PGSSLMODE' => $config['sslmode'] ?? 'require',
        'PGCHANNELBINDING' => $config['channel_binding'] ?? 'require',
        'PGCONNECT_TIMEOUT' => 15,
    ];
    foreach ($variables as $key => $value) {
        if (preg_match('/[\r\n]/', (string) $value)) {
            throw new RuntimeException('Invalid multiline backup configuration.');
        }
        echo $key.'='.$value.PHP_EOL;
    }
    exit;
}

if ($operation === 'protect-cms') {
    if (! $app->isProduction()) {
        throw new RuntimeException('CMS password protection must run in production.');
    }
    $changed = [];
    foreach (User::whereIn('email', ['admin@biology.learn', 'penulis@biology.learn'])->get() as $user) {
        if (Hash::check('password', $user->password)) {
            $password = Str::password(24);
            $user->forceFill(['password' => Hash::make($password)])->save();
            $changed[] = ['email' => $user->email, 'password' => $password];
        }
    }
    if ($changed !== []) {
        $path = storage_path('app/private/deployment-cms-credentials.json');
        if (! is_dir(dirname($path))) {
            mkdir(dirname($path), 0700, true);
        }
        file_put_contents($path, json_encode($changed, JSON_PRETTY_PRINT | JSON_THROW_ON_ERROR));
        chmod($path, 0600);
    }
    echo 'Default CMS passwords replaced: '.count($changed).PHP_EOL;
    exit;
}

if ($operation !== 'status') {
    throw new InvalidArgumentException('Unknown operation.');
}

$counts = [];
foreach (['users', 'articles', 'courses', 'products', 'warehouses', 'merchants', 'transactions'] as $table) {
    if (Schema::hasTable($table)) {
        $counts[$table] = DB::table($table)->count();
    }
}
echo json_encode([
    'environment' => $app->environment(),
    'debug' => config('app.debug'),
    'url' => config('app.url'),
    'database' => config('database.default'),
    'secure_cookie' => config('session.secure'),
    'counts' => $counts,
], JSON_PRETTY_PRINT | JSON_THROW_ON_ERROR).PHP_EOL;
