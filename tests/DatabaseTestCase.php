<?php

declare(strict_types=1);

namespace N3XT0R\LaravelWebdavServer\Tests;

use Illuminate\Contracts\Config\Repository;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Orchestra\Testbench\Attributes\WithMigration;

#[WithMigration]
class DatabaseTestCase extends TestCase
{
    use LazilyRefreshDatabase;

    protected function defineEnvironment($app): void
    {
        $config = $app->make(Repository::class);

        $config->set([
            'database.default' => 'testing',
            'database.connections.testing' => [
                'driver' => 'sqlite',
                'database' => ':memory:',
                'prefix' => '',
                'foreign_key_constraints' => false,
            ],
        ]);
    }
}
