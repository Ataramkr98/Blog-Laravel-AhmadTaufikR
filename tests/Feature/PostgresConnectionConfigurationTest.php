<?php

use App\Database\Connectors\PostgresConnector;
use App\Models\Glossary;
use Illuminate\Support\ConfigurationUrlParser;

function postgresDsnForTest(array $config): string
{
    return (new class extends PostgresConnector
    {
        public function dsn(array $config): string
        {
            return $this->getDsn($config);
        }
    })->dsn($config);
}

test('postgres url security options reach libpq through the registered connector', function () {
    $config = (new ConfigurationUrlParser)->parseConfiguration([
        'url' => 'postgresql://demo:example-password@database-pooler.example.test/neondb?sslmode=require&channel_binding=require',
        'port' => 5432,
        'charset' => 'utf8',
    ]);

    expect(app('db.factory')->createConnector($config))->toBeInstanceOf(PostgresConnector::class)
        ->and($config['driver'])->toBe('pgsql')
        ->and(postgresDsnForTest($config))->toContain('host=database-pooler.example.test;', 'dbname=\'neondb\'', 'port=5432', 'sslmode=require', 'channel_binding=require', 'connect_timeout=15');
});

test('postgres connector rejects malformed security options before connecting', function (array $options) {
    postgresDsnForTest(array_merge(['host' => 'localhost', 'database' => 'testing'], $options));
})->with([
    [['channel_binding' => 'require;sslmode=disable']],
    [['channel_binding' => ['require']]],
    [['connect_timeout' => 0]],
    [['connect_timeout' => 121]],
])->throws(InvalidArgumentException::class);

test('pooled postgres queries use emulated prepares while migrations use the direct endpoint', function () {
    $connection = app('db.factory')->make([
        'driver' => 'pgsql',
        'host' => 'database-pooler.example.test',
        'database' => 'testing',
        'username' => 'demo',
        'password' => 'example-password',
        'prefix' => '',
        'pooled' => true,
        'sslmode' => 'require',
        'channel_binding' => 'require',
        'direct' => ['host' => 'database.example.test'],
    ], 'neon-config-test');

    expect($connection->getConfig('options')[PDO::ATTR_EMULATE_PREPARES])->toBeTrue()
        ->and($connection->getDirectPdoConfig()['host'])->toBe('database.example.test')
        ->and($connection->getDirectPdoConfig()['channel_binding'])->toBe('require')
        ->and($connection->getDirectPdoConfig()['options'][PDO::ATTR_EMULATE_PREPARES])->toBeFalse();
});

test('glossary search keeps case insensitive matching on postgresql', function () {
    config(['database.connections.postgres_search_test' => [
        'driver' => 'pgsql', 'host' => 'database.example.test', 'database' => 'testing',
        'username' => 'demo', 'password' => 'example-password', 'prefix' => '',
    ]]);

    $query = Glossary::on('postgres_search_test')->search('atp');

    expect($query->toSql())->toContain('"term"::text ilike ?', '"common_name"::text ilike ?', '"definition"::text ilike ?')
        ->and($query->getBindings())->toBe(['%atp%', '%atp%', '%atp%']);
});
