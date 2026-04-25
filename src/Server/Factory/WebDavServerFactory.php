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
    /**
     * Create the WebDAV server factory that orchestrates request context resolution and SabreDAV server construction.
     *
     * @param \N3XT0R\LaravelWebdavServer\Contracts\Server\RequestContextResolverInterface $requestContextResolver Resolver that gathers principal, space key, and storage space for the request.
     * @param \N3XT0R\LaravelWebdavServer\Contracts\Server\StorageRootBuilderInterface $storageRootBuilder Builder that creates the SabreDAV tree root for the resolved space.
     * @param \N3XT0R\LaravelWebdavServer\Contracts\Server\ServerConfiguratorInterface $serverConfigurator Configurator that applies runtime settings such as base URI and logger.
     */
    public function __construct(
        private RequestContextResolverInterface $requestContextResolver,
        private StorageRootBuilderInterface $storageRootBuilder,
        private ServerConfiguratorInterface $serverConfigurator,
    ) {}

    /**
     * Build the SabreDAV server instance for the incoming WebDAV request.
     *
     * @param \Illuminate\Http\Request $request Incoming HTTP request targeting the WebDAV endpoint.
     *
     * @throws \N3XT0R\LaravelWebdavServer\Exception\DomainException When credentials, auth, or storage resolution fails during server construction.
     *
     * @return \Sabre\DAV\Server Configured SabreDAV server ready for runtime execution.
     */
    public function make(Request $request): Server
    {
        $context = $this->requestContextResolver->resolve($request);

        $root = $this->storageRootBuilder->build($context->principal, $context->space);

        $server = new Server($root);
        $this->serverConfigurator->configure($server, $context->spaceKey);

        return $server;
    }
}
