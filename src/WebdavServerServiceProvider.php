<?php

declare(strict_types=1);

namespace N3XT0R\LaravelWebdavServer;

use Illuminate\Support\Facades\Gate;
use N3XT0R\LaravelWebdavServer\Commands\CreateWebDavAccountCommand;
use N3XT0R\LaravelWebdavServer\Commands\LaravelWebdavServerCommand;
use N3XT0R\LaravelWebdavServer\Commands\ListWebDavAccountsCommand;
use N3XT0R\LaravelWebdavServer\Commands\ShowWebDavAccountCommand;
use N3XT0R\LaravelWebdavServer\Commands\UpdateWebDavAccountCommand;
use N3XT0R\LaravelWebdavServer\DTO\Auth\PathResourceDto;
use N3XT0R\LaravelWebdavServer\Policies\PathPolicy;
use N3XT0R\LaravelWebdavServer\Providers\Registers\WebDavRegisterFactory;
use Spatie\LaravelPackageTools\Package;
use Spatie\LaravelPackageTools\PackageServiceProvider;

class WebdavServerServiceProvider extends PackageServiceProvider
{
    public const SABRE_PLUGIN_TAG = 'laravel-webdav-server.sabre-plugin';

    /**
     * Declares the package resources that Laravel should register for this package.
     *
     * @param  Package  $package  Package-tools configuration object that collects commands, config, views, and migrations.
     */
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
            ->discoversMigrations()
            ->hasCommand(LaravelWebdavServerCommand::class)
            ->hasCommand(CreateWebDavAccountCommand::class)
            ->hasCommand(ListWebDavAccountsCommand::class)
            ->hasCommand(ShowWebDavAccountCommand::class)
            ->hasCommand(UpdateWebDavAccountCommand::class);
    }

    /**
     * Registers the package WebDAV routes from the bundled route file.
     */
    protected function registerRoutes(): void
    {
        $this->loadRoutesFrom(__DIR__.'/../routes/web.php');
    }

    /**
     * Registers all package bindings after the package itself has been registered.
     */
    public function packageRegistered(): void
    {
        $this->app->make(WebDavRegisterFactory::class)->registerAll();
    }

    /**
     * Finalizes package bootstrapping by wiring CSRF exceptions, routes, and the default path policy.
     */
    public function packageBooted(): void
    {
        parent::packageBooted();
        $this->registerCsrfException();
        $this->registerRoutes();
        Gate::policy(PathResourceDto::class, PathPolicy::class);
    }

    /**
     * Return the container tag applications can use to register additional SabreDAV plugins.
     *
     * Tagged plugins are added alongside the package defaults during `SabreServerConfigurator::configure()`.
     *
     * @return string Container tag for user-defined SabreDAV `ServerPlugin` instances.
     */
    public static function sabrePluginTag(): string
    {
        return self::SABRE_PLUGIN_TAG;
    }

    private function registerCsrfException(): void
    {
        $routePrefix = trim((string) config('webdav-server.route_prefix', ''), '/');

        if ($routePrefix === '') {
            $routePrefix = trim((string) config('webdav-server.base_uri', ''), '/');
        }

        if ($routePrefix === '') {
            return;
        }

        foreach ($this->csrfMiddlewareClasses() as $middlewareClass) {
            if (! class_exists($middlewareClass)) {
                continue;
            }

            // Exclude package WebDAV endpoints from CSRF checks.
            $middlewareClass::except([
                $routePrefix,
                $routePrefix.'/*',
            ]);

            return;
        }
    }

    /**
     * @return list<class-string>
     */
    private function csrfMiddlewareClasses(): array
    {
        return [
            'Illuminate\\Foundation\\Http\\Middleware\\PreventRequestForgery',
            'Illuminate\\Foundation\\Http\\Middleware\\VerifyCsrfToken',
        ];
    }
}
