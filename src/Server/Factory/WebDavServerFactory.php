<?php

declare(strict_types=1);

namespace N3XT0R\LaravelWebdavServer\Server\Factory;

use Illuminate\Http\Request;
use N3XT0R\LaravelWebdavServer\Contracts\Server\RequestContextResolverInterface;
use N3XT0R\LaravelWebdavServer\Contracts\Server\ServerConfiguratorInterface;
use N3XT0R\LaravelWebdavServer\Contracts\Server\StorageRootBuilderInterface;
use Sabre\DAV\Server;

final readonly class WebDavServerFactory
{
    public function __construct(
        private RequestContextResolverInterface $requestContextResolver,
        private StorageRootBuilderInterface $storageRootBuilder,
        private ServerConfiguratorInterface $serverConfigurator,
    ) {}

    public function make(Request $request): Server
    {
        $context = $this->requestContextResolver->resolve($request);

        $root = $this->storageRootBuilder->build($context->principal, $context->space);

        $server = new Server($root);
        $this->serverConfigurator->configure($server, $context->spaceKey);

        return $server;
    }
}
