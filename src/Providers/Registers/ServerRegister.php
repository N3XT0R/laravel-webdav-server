<?php

declare(strict_types=1);

namespace N3XT0R\LaravelWebdavServer\Providers\Registers;

use N3XT0R\LaravelWebdavServer\Contracts\Server\PrincipalAuthenticatorInterface;
use N3XT0R\LaravelWebdavServer\Contracts\Server\RequestContextResolverInterface;
use N3XT0R\LaravelWebdavServer\Contracts\Server\RequestCredentialsExtractorInterface;
use N3XT0R\LaravelWebdavServer\Contracts\Server\ServerConfiguratorInterface;
use N3XT0R\LaravelWebdavServer\Contracts\Server\ServerRunnerInterface;
use N3XT0R\LaravelWebdavServer\Contracts\Server\SpaceKeyResolverInterface;
use N3XT0R\LaravelWebdavServer\Server\Auth\ValidatorPrincipalAuthenticator;
use N3XT0R\LaravelWebdavServer\Server\Configuration\SabreServerConfigurator;
use N3XT0R\LaravelWebdavServer\Server\Request\Auth\RequestBasicCredentialsExtractor;
use N3XT0R\LaravelWebdavServer\Server\Request\Context\DefaultRequestContextResolver;
use N3XT0R\LaravelWebdavServer\Server\Request\Routing\RequestSpaceKeyResolver;
use N3XT0R\LaravelWebdavServer\Server\Runtime\SabreServerRunner;

final readonly class ServerRegister extends AbstractRegister
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
}
