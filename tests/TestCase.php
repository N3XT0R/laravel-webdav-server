<?php

namespace N3XT0R\LaravelWebdavServer\Tests;

use Illuminate\Database\Eloquent\Factories\Factory;
use N3XT0R\LaravelWebdavServer\LaravelWebdavServerServiceProvider;
use Orchestra\Testbench\TestCase as Orchestra;

class TestCase extends Orchestra
{
    protected function setUp(): void
    {
        parent::setUp();

        Factory::guessFactoryNamesUsing(
            fn(string $modelName
            ) => 'N3XT0R\\LaravelWebdavServer\\Database\\Factories\\'.class_basename($modelName).'Factory'
        );
    }

    protected function getPackageProviders($app): array
    {
        return [
            LaravelWebdavServerServiceProvider::class,
        ];
    }
}
