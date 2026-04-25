<?php

declare(strict_types=1);

namespace N3XT0R\LaravelWebdavServer\Server\Factory;

use Illuminate\Http\Request;
use N3XT0R\LaravelWebdavServer\Contracts\Server\RequestContextResolverInterface;
use N3XT0R\LaravelWebdavServer\Contracts\Server\ServerConfiguratorInterface;
use N3XT0R\LaravelWebdavServer\Contracts\Server\StorageRootBuilderInterface;
use N3XT0R\LaravelWebdavServer\Exception\DomainException;
use N3XT0R\LaravelWebdavServer\Logging\WebDavLoggingService;
use Sabre\DAV\Server;

final readonly class WebDavServerFactory
{
    /**
     * Create the WebDAV server factory that orchestrates request context resolution and SabreDAV server construction.
     *
     * @param  RequestContextResolverInterface  $requestContextResolver  Resolver that gathers principal, space key, and storage space for the request.
     * @param  StorageRootBuilderInterface  $storageRootBuilder  Builder that creates the SabreDAV tree root for the resolved space.
     * @param  ServerConfiguratorInterface  $serverConfigurator  Configurator that applies runtime settings such as base URI and logger.
     * @param  WebDavLoggingService  $logger  Package logger used to trace server construction.
     */
    public function __construct(
        private RequestContextResolverInterface $requestContextResolver,
        private StorageRootBuilderInterface $storageRootBuilder,
        private ServerConfiguratorInterface $serverConfigurator,
        private WebDavLoggingService $logger,
    ) {}

    /**
     * Build the SabreDAV server instance for the incoming WebDAV request.
     *
     * @param  Request  $request  Incoming HTTP request targeting the WebDAV endpoint.
     * @return Server Configured SabreDAV server ready for runtime execution.
     *
     * @throws DomainException When credentials, auth, or storage resolution fails during server construction.
     */
    public function make(Request $request): Server
    {
        $this->logger->debug('Building SabreDAV server for WebDAV request.', [
            'request' => [
                'method' => $request->getMethod(),
                'uri' => $request->getRequestUri(),
            ],
        ]);

        $context = $this->requestContextResolver->resolve($request);

        $root = $this->storageRootBuilder->build($context->principal, $context->space);

        $server = new Server($root);
        $this->serverConfigurator->configure($server, $context->spaceKey);

        $this->logger->debug('Built SabreDAV server for WebDAV request.', [
            'webdav' => [
                'principal_id' => $context->principal->id,
                'space_key' => $context->spaceKey,
                'disk' => $context->space->disk,
                'root_path' => $context->space->rootPath,
            ],
        ]);

        return $server;
    }
}
