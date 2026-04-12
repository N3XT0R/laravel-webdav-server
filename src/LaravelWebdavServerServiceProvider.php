<?php

namespace N3XT0R\LaravelWebdavServer;

use N3XT0R\LaravelWebdavServer\Commands\LaravelWebdavServerCommand;
use N3XT0R\LaravelWebdavServer\Http\Server\WebDavServerFactory;
use Spatie\LaravelPackageTools\Package;
use Spatie\LaravelPackageTools\PackageServiceProvider;

class LaravelWebdavServerServiceProvider extends PackageServiceProvider
{
    public function configurePackage(Package $package): void
    {
        /*
         * This class is a Package Service Provider
         *
         * More info: https://github.com/spatie/laravel-package-tools
         */
        $package
            ->name('laravel-webdav-server')
            ->hasConfigFile()
            ->hasViews()
            ->hasMigration('create_laravel_webdav_server_table')
            ->hasCommand(LaravelWebdavServerCommand::class);
    }

    protected function registerRoutes(): void
    {
        $this->loadRoutesFrom(__DIR__.'/../routes/web.php');
    }

    public function packageRegistered(): void
    {
        $this->app->scoped(WebDavServerFactory::class, function ($app) {
            return new WebDavServerFactory($app);
        });
    }

    public function packageBooted(): void
    {
        parent::packageBooted();
        $this->registerRoutes();
    }
}
