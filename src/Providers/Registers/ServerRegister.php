<?php

declare(strict_types=1);

namespace N3XT0R\LaravelWebdavServer\Providers\Registers;

use Illuminate\Container\Container;
use N3XT0R\LaravelWebdavServer\Contracts\Server\PrincipalAuthenticatorInterface;
use N3XT0R\LaravelWebdavServer\Contracts\Server\RequestContextResolverInterface;
use N3XT0R\LaravelWebdavServer\Contracts\Server\RequestCredentialsExtractorInterface;
use N3XT0R\LaravelWebdavServer\Contracts\Server\ServerConfiguratorInterface;
use N3XT0R\LaravelWebdavServer\Contracts\Server\SpaceKeyResolverInterface;
use N3XT0R\LaravelWebdavServer\Contracts\Server\StorageRootBuilderInterface;
use N3XT0R\LaravelWebdavServer\Server\Auth\ValidatorPrincipalAuthenticator;
use N3XT0R\LaravelWebdavServer\Server\Configuration\SabreServerConfigurator;
use N3XT0R\LaravelWebdavServer\Server\Factory\WebDavServerFactory;
use N3XT0R\LaravelWebdavServer\Server\Request\Auth\RequestBasicCredentialsExtractor;
use N3XT0R\LaravelWebdavServer\Server\Request\Context\DefaultRequestContextResolver;
use N3XT0R\LaravelWebdavServer\Server\Request\Routing\RequestSpaceKeyResolver;

final readonly class ServerRegister
{
    public function __construct(
        private Container $app,
    ) {}

    public function register(): void
    {
        $this->app->bindIf(
            RequestCredentialsExtractorInterface::class,
            RequestBasicCredentialsExtractor::class,
        );

        $this->app->bindIf(
            PrincipalAuthenticatorInterface::class,
            ValidatorPrincipalAuthenticator::class,
        );

        $this->app->bindIf(
            SpaceKeyResolverInterface::class,
            RequestSpaceKeyResolver::class,
        );

        $this->app->bindIf(
            RequestContextResolverInterface::class,
            DefaultRequestContextResolver::class,
        );

        $this->app->bindIf(
            ServerConfiguratorInterface::class,
            SabreServerConfigurator::class,
        );

        $this->app->scopedIf(WebDavServerFactory::class, function (Container $app): WebDavServerFactory {
            return new WebDavServerFactory(
                requestContextResolver: $app->make(RequestContextResolverInterface::class),
                storageRootBuilder: $app->make(StorageRootBuilderInterface::class),
                serverConfigurator: $app->make(ServerConfiguratorInterface::class),
            );
        });
    }
}
