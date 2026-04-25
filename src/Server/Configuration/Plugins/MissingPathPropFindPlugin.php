<?php

declare(strict_types=1);

namespace N3XT0R\LaravelWebdavServer\Server\Configuration\Plugins;

use N3XT0R\LaravelWebdavServer\Logging\WebDavLoggingService;
use Sabre\DAV\Server;
use Sabre\DAV\ServerPlugin;
use Sabre\HTTP\RequestInterface;
use Sabre\HTTP\ResponseInterface;
use Sabre\Uri;

final class MissingPathPropFindPlugin extends ServerPlugin
{
    private Server $server;

    /**
     * Create the plugin that turns missing-target PROPFIND probes into normal 404 DAV responses.
     *
     * @param  WebDavLoggingService  $logger  Package logger used to trace handled missing-path PROPFIND probes.
     */
    public function __construct(
        private readonly WebDavLoggingService $logger,
    ) {}

    /**
     * Register the PROPFIND pre-handler for missing target paths.
     *
     * @param  Server  $server  SabreDAV server instance whose PROPFIND handling should be extended.
     */
    public function initialize(Server $server): void
    {
        $this->server = $server;

        $server->on('method:PROPFIND', [$this, 'handleMissingTargetPropFind'], 10);
    }

    /**
     * Answer missing-target PROPFIND requests with a normal 404 response instead of bubbling a DAV exception.
     *
     * @param  RequestInterface  $request  Incoming SabreDAV PROPFIND request.
     * @param  ResponseInterface  $response  SabreDAV response that will be sent to the client.
     * @return bool|null `false` when the plugin handled the response, otherwise `null` to continue the normal flow.
     */
    public function handleMissingTargetPropFind(RequestInterface $request, ResponseInterface $response): ?bool
    {
        $path = trim($request->getPath(), '/');

        if ($path === '' || $this->server->tree->nodeExists($path)) {
            return null;
        }

        [$parentPath] = Uri\split($path);

        if ($parentPath !== '' && ! $this->server->tree->nodeExists($parentPath)) {
            return null;
        }

        $response->setStatus(404);
        $response->setBody('');

        $this->logger->debug('Handled missing WebDAV target path during PROPFIND without raising a DAV exception.', [
            'request' => [
                'method' => $request->getMethod(),
                'uri' => $request->getUrl(),
                'path' => $request->getPath(),
                'depth' => $request->getHeader('Depth'),
            ],
            'webdav' => [
                'base_uri' => $this->server->getBaseUri(),
                'parent_path' => $parentPath,
            ],
        ]);

        return false;
    }
}
