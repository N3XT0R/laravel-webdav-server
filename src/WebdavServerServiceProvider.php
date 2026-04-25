<?php

declare(strict_types=1);

namespace N3XT0R\LaravelWebdavServer;

use Illuminate\Support\Facades\Gate;
use N3XT0R\LaravelWebdavServer\Commands\LaravelWebdavServerCommand;
use N3XT0R\LaravelWebdavServer\DTO\Auth\PathResourceDto;
use N3XT0R\LaravelWebdavServer\Policies\PathPolicy;
use N3XT0R\LaravelWebdavServer\Providers\Registers\WebDavRegisterFactory;
use Spatie\LaravelPackageTools\Package;
use Spatie\LaravelPackageTools\PackageServiceProvider;

class WebdavServerServiceProvider extends PackageServiceProvider
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
            ->discoversMigrations()
            ->hasCommand(LaravelWebdavServerCommand::class);
    }

    protected function registerRoutes(): void
    {
        $this->loadRoutesFrom(__DIR__.'/../routes/web.php');
    }

    public function packageRegistered(): void
    {
        $this->app->make(WebDavRegisterFactory::class)->registerAll();
    }

    public function packageBooted(): void
    {
        parent::packageBooted();
        $this->registerCsrfException();
        $this->registerRoutes();
        Gate::policy(PathResourceDto::class, PathPolicy::class);
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
