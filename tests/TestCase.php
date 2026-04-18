<?php

namespace N3XT0R\LaravelWebdavServer\Tests;

use Illuminate\Contracts\Config\Repository;
use Illuminate\Database\Eloquent\Factories\Factory;
use N3XT0R\LaravelWebdavServer\WebdavServerServiceProvider;
use Orchestra\Testbench\Concerns\WithWorkbench;
use Orchestra\Testbench\TestCase as Orchestra;
use Workbench\App\Models\User;

class TestCase extends Orchestra
{
    use WithWorkbench;

    protected function setUp(): void
    {
        parent::setUp();

        Factory::guessFactoryNamesUsing(
            static fn (string $modelName) => 'N3XT0R\\LaravelWebdavServer\\Database\\Factories\\'.class_basename(
                $modelName
            ).'Factory'
        );
    }

    protected function defineEnvironment($app): void
    {
        parent::defineEnvironment($app);
        $config = $app->make(Repository::class);
        $config->set('webdav-server.auth.user_model', User::class);
    }

    protected function getPackageProviders($app): array
    {
        return [
            WebdavServerServiceProvider::class,
        ];
    }
}
