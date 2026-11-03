<?php

namespace App\Database\Connectors;

use Illuminate\Database\Connectors\PostgresConnector as BasePostgresConnector;
use InvalidArgumentException;

class PostgresConnector extends BasePostgresConnector
{
    protected function getDsn(array $config): string
    {
        $binding = $config['channel_binding'] ?? 'prefer';
        $timeout = filter_var($config['connect_timeout'] ?? 15, FILTER_VALIDATE_INT, [
            'options' => ['min_range' => 1, 'max_range' => 120],
        ]);

        if (! in_array($binding, ['disable', 'prefer', 'require'], true) || $timeout === false) {
            throw new InvalidArgumentException('Invalid PostgreSQL channel binding or connection timeout.');
        }

        // Laravel does not forward these libpq options from database URLs by default.
        return parent::getDsn($config).";channel_binding={$binding};connect_timeout={$timeout}";
    }
}
