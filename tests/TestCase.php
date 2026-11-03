<?php

namespace Tests;

use Illuminate\Foundation\Testing\TestCase as BaseTestCase;
use LogicException;

abstract class TestCase extends BaseTestCase
{
    public function createApplication()
    {
        $app = parent::createApplication();
        $database = $app['config']['database'];

        if ($database['default'] !== 'sqlite' || $database['connections']['sqlite']['database'] !== ':memory:' || filled($database['connections']['sqlite']['url'])) {
            throw new LogicException('Feature tests require an isolated in-memory SQLite database.');
        }

        return $app;
    }
}
