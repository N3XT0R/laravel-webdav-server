<?php

namespace N3XT0R\LaravelWebdavServer;

use Illuminate\Container\Container as Application;
use Illuminate\Contracts\Filesystem\Factory;
use Illuminate\Support\Facades\Gate;
use N3XT0R\LaravelWebdavServer\Auth\Authorization\GatePathAuthorization;
use N3XT0R\LaravelWebdavServer\Auth\Validators\DatabaseCredentialValidator;
use N3XT0R\LaravelWebdavServer\Commands\LaravelWebdavServerCommand;
use N3XT0R\LaravelWebdavServer\Contracts\Auth\CredentialValidatorInterface;
use N3XT0R\LaravelWebdavServer\Contracts\Auth\PathAuthorizationInterface;
use N3XT0R\LaravelWebdavServer\Contracts\Repositories\WebDavAccountRepositoryInterface;
use N3XT0R\LaravelWebdavServer\Contracts\Storage\SpaceResolverInterface;
use N3XT0R\LaravelWebdavServer\DTO\Auth\WebDavPathResourceDto;
use N3XT0R\LaravelWebdavServer\Policies\WebDavPathPolicy;
use N3XT0R\LaravelWebdavServer\Repositories\EloquentWebDavAccountRepository;
use N3XT0R\LaravelWebdavServer\Server\WebDavServerFactory;
use N3XT0R\LaravelWebdavServer\Storage\Resolvers\DefaultSpaceResolver;
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
        $this->app->bindIf(
            WebDavAccountRepositoryInterface::class,
            EloquentWebDavAccountRepository::class,
        );

        $this->app->bindIf(
            CredentialValidatorInterface::class,
            DatabaseCredentialValidator::class,
        );

        $this->app->bindIf(
            SpaceResolverInterface::class,
            DefaultSpaceResolver::class,
        );

        $this->app->bindIf(
            PathAuthorizationInterface::class,
            GatePathAuthorization::class,
        );

        $this->app->scopedIf(WebDavServerFactory::class, function (Application $app): WebDavServerFactory {
            return new WebDavServerFactory(
                validator: $app->make(CredentialValidatorInterface::class),
                spaceResolver: $app->make(SpaceResolverInterface::class),
                authorization: $app->make(PathAuthorizationInterface::class),
                filesystem: $app->make(Factory::class),
            );
        });
    }

    public function packageBooted(): void
    {
        parent::packageBooted();
        $this->registerCsrfException();
        $this->registerRoutes();
        Gate::policy(WebDavPathResourceDto::class, WebDavPathPolicy::class);
    }

    private function registerCsrfException(): void
    {
        $routePrefix = trim((string)config('webdav-server.route_prefix', ''), '/');

        if ($routePrefix === '') {
            $routePrefix = trim((string)config('webdav-server.base_uri', ''), '/');
        }

        if ($routePrefix === '') {
            return;
        }

        $csrfMiddleware = $this->resolveCsrfMiddlewareClass();

        if ($csrfMiddleware === null) {
            return;
        }

        // Exclude package WebDAV endpoints from CSRF checks.
        call_user_func([$csrfMiddleware, 'except'], [
            $routePrefix,
            $routePrefix.'/*',
        ]);
    }

    private function resolveCsrfMiddlewareClass(): ?string
    {
        $candidates = [
            'Illuminate\\Foundation\\Http\\Middleware\\PreventRequestForgery',
            'Illuminate\\Foundation\\Http\\Middleware\\VerifyCsrfToken',
        ];

        foreach ($candidates as $middlewareClass) {
            if (class_exists($middlewareClass) && method_exists($middlewareClass, 'except')) {
                return $middlewareClass;
            }
        }

        return null;
    }
}
