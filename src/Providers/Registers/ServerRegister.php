<?php

declare(strict_types=1);

namespace N3XT0R\LaravelWebdavServer\Providers\Registers;

use Illuminate\Container\Container;
use N3XT0R\LaravelWebdavServer\Contracts\Server\PrincipalAuthenticatorInterface;
use N3XT0R\LaravelWebdavServer\Contracts\Server\RequestContextResolverInterface;
use N3XT0R\LaravelWebdavServer\Contracts\Server\RequestCredentialsExtractorInterface;
use N3XT0R\LaravelWebdavServer\Contracts\Server\ServerConfiguratorInterface;
use N3XT0R\LaravelWebdavServer\Contracts\Server\ServerRunnerInterface;
use N3XT0R\LaravelWebdavServer\Contracts\Server\SpaceKeyResolverInterface;
use N3XT0R\LaravelWebdavServer\Contracts\Server\StorageRootBuilderInterface;
use N3XT0R\LaravelWebdavServer\Server\Auth\ValidatorPrincipalAuthenticator;
use N3XT0R\LaravelWebdavServer\Server\Configuration\SabreServerConfigurator;
use N3XT0R\LaravelWebdavServer\Server\Factory\WebDavServerFactory;
use N3XT0R\LaravelWebdavServer\Server\Request\Auth\RequestBasicCredentialsExtractor;
use N3XT0R\LaravelWebdavServer\Server\Request\Context\DefaultRequestContextResolver;
use N3XT0R\LaravelWebdavServer\Server\Request\Routing\RequestSpaceKeyResolver;
use N3XT0R\LaravelWebdavServer\Server\Runtime\SabreServerRunner;

final class ServerRegister extends AbstractRegister
{
    protected function bindings(): array
    {
        return [
            RequestCredentialsExtractorInterface::class => RequestBasicCredentialsExtractor::class,
            PrincipalAuthenticatorInterface::class => ValidatorPrincipalAuthenticator::class,
            SpaceKeyResolverInterface::class => RequestSpaceKeyResolver::class,
            RequestContextResolverInterface::class => DefaultRequestContextResolver::class,
            ServerConfiguratorInterface::class => SabreServerConfigurator::class,
            ServerRunnerInterface::class => SabreServerRunner::class,
        ];
    }

    public function register(): void
    {
        parent::register();

        $this->app->scopedIf(WebDavServerFactory::class, function (Container $app): WebDavServerFactory {
            return new WebDavServerFactory(
                requestContextResolver: $app->make(RequestContextResolverInterface::class),
                storageRootBuilder: $app->make(StorageRootBuilderInterface::class),
                serverConfigurator: $app->make(ServerConfiguratorInterface::class),
            );
        });
    }
}
