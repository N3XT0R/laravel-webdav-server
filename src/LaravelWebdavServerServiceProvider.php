<?php

namespace N3XT0R\LaravelWebdavServer;

use Illuminate\Contracts\Filesystem\Factory;
use N3XT0R\LaravelWebdavServer\Auth\Validators\DatabaseCredentialValidator;
use N3XT0R\LaravelWebdavServer\Commands\LaravelWebdavServerCommand;
use N3XT0R\LaravelWebdavServer\Contracts\Auth\CredentialValidatorInterface;
use N3XT0R\LaravelWebdavServer\Contracts\Repositories\WebDavAccountRepositoryInterface;
use N3XT0R\LaravelWebdavServer\Contracts\Storage\SpaceResolverInterface;
use N3XT0R\LaravelWebdavServer\Repositories\EloquentWebDavAccountRepository;
use N3XT0R\LaravelWebdavServer\Server\WebDavServerFactory;
use Spatie\LaravelPackageTools\Package;
use Spatie\LaravelPackageTools\PackageServiceProvider;
use Illuminate\Container\Container as Application;

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
        $this->app->singleton(WebDavServerFactory::class, function (Application $app): WebDavServerFactory {
            return new WebDavServerFactory(
                validator: $app->make(CredentialValidatorInterface::class),
                spaceResolver: $app->make(SpaceResolverInterface::class),
                filesystem: $app->make(Factory::class),
            );
        });
    }

    public function packageBooted(): void
    {
        parent::packageBooted();
        $this->registerRoutes();

        $this->app->bindIf(
            WebDavAccountRepositoryInterface::class,
            EloquentWebDavAccountRepository::class,
        );

        $this->app->bindIf(
            CredentialValidatorInterface::class,
            DatabaseCredentialValidator::class,
        );
    }
}
