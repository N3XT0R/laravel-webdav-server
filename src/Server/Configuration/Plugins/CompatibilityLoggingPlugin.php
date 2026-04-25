<?php

declare(strict_types=1);

namespace N3XT0R\LaravelWebdavServer\Server\Configuration\Plugins;

use N3XT0R\LaravelWebdavServer\Logging\WebDavLoggingService;
use Sabre\DAV\Server;
use Sabre\DAV\ServerPlugin;
use Sabre\HTTP\RequestInterface;
use Sabre\HTTP\ResponseInterface;

final class CompatibilityLoggingPlugin extends ServerPlugin
{
    /**
     * Create the plugin that traces Windows-relevant DAV method handling.
     *
     * @param  WebDavLoggingService  $logger  Package logger used to trace DAV protocol handling without logging secrets.
     */
    public function __construct(
        private WebDavLoggingService $logger,
    ) {}

    /**
     * Register request-lifecycle listeners for DAV compatibility tracing.
     *
     * @param  Server  $server  SabreDAV server whose request lifecycle should be traced.
     */
    public function initialize(Server $server): void
    {
        $server->on('beforeMethod:*', function (RequestInterface $request, ResponseInterface $response) use ($server): void {
            $path = $request->getPath();

            $this->logger->debug('Handling WebDAV request inside SabreDAV.', [
                'request' => [
                    'method' => $request->getMethod(),
                    'uri' => $request->getUrl(),
                    'path' => $path,
                    'depth' => $request->getHeader('Depth'),
                ],
                'webdav' => [
                    'base_uri' => $server->getBaseUri(),
                    'is_root_request' => $path === '',
                ],
            ]);

            if ($request->getMethod() === 'PROPFIND' && $path === '') {
                $this->logger->debug('Handling PROPFIND for the WebDAV root collection.', [
                    'request' => [
                        'depth' => $request->getHeader('Depth'),
                        'uri' => $request->getUrl(),
                    ],
                    'webdav' => [
                        'base_uri' => $server->getBaseUri(),
                    ],
                ]);
            }
        });

        $server->on('afterMethod:OPTIONS', function (RequestInterface $request, ResponseInterface $response) use ($server): void {
            $this->logger->debug('Completed WebDAV OPTIONS request.', [
                'request' => [
                    'uri' => $request->getUrl(),
                ],
                'webdav' => [
                    'base_uri' => $server->getBaseUri(),
                    'allow' => $response->getHeader('Allow'),
                    'dav' => $response->getHeader('DAV'),
                ],
            ]);
        });

        $server->on('afterMethod:PROPFIND', function (RequestInterface $request, ResponseInterface $response) use ($server): void {
            $this->logger->debug('Completed WebDAV PROPFIND request.', [
                'request' => [
                    'uri' => $request->getUrl(),
                    'path' => $request->getPath(),
                    'depth' => $request->getHeader('Depth'),
                ],
                'webdav' => [
                    'base_uri' => $server->getBaseUri(),
                    'status' => $response->getStatus(),
                    'content_type' => $response->getHeader('Content-Type'),
                ],
            ]);
        });
    }
}
