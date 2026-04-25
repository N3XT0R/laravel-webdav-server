<?php

declare(strict_types=1);

namespace N3XT0R\LaravelWebdavServer\Providers\Registers;

use Illuminate\Container\Container;
use N3XT0R\LaravelWebdavServer\Contracts\Server\RequestContextResolverInterface;
use N3XT0R\LaravelWebdavServer\Contracts\Server\ServerConfiguratorInterface;
use N3XT0R\LaravelWebdavServer\Contracts\Server\StorageRootBuilderInterface;
use N3XT0R\LaravelWebdavServer\Server\Factory\WebDavServerFactory;

final readonly class ServerFactoryRegister extends AbstractRegister
{
    protected function bindings(): array
    {
        return [];
    }

    /**
     * Registers the scoped WebDAV server factory that builds one configured SabreDAV server per request.
     *
     * @return void
     */
    public function register(): void
    {
        $this->app->scopedIf(WebDavServerFactory::class, function (Container $app): WebDavServerFactory {
            return new WebDavServerFactory(
                requestContextResolver: $app->make(RequestContextResolverInterface::class),
                storageRootBuilder: $app->make(StorageRootBuilderInterface::class),
                serverConfigurator: $app->make(ServerConfiguratorInterface::class),
            );
        });
    }
}
