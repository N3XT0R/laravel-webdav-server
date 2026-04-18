<?php

namespace N3XT0R\LaravelWebdavServer\Tests\Unit;

use Illuminate\Database\Eloquent\Factories\Factory;
use N3XT0R\LaravelWebdavServer\WebdavServerServiceProvider;
use Orchestra\Testbench\Concerns\WithWorkbench;
use Orchestra\Testbench\TestCase as Orchestra;

class TestCase extends Orchestra
{
    use WithWorkbench;

    protected function setUp(): void
    {
        parent::setUp();

        Factory::guessFactoryNamesUsing(
            static fn(string $modelName) => 'N3XT0R\\LaravelWebdavServer\\Database\\Factories\\'.class_basename(
                    $modelName
                ).'Factory'
        );
    }

    protected function getPackageProviders($app): array
    {
        return [
            WebdavServerServiceProvider::class,
        ];
    }
}
