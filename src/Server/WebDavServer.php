<?php

declare(strict_types=1);

namespace N3XT0R\LaravelWebdavServer\Server;

use N3XT0R\LaravelWebdavServer\Auth\Backends\BasicAuthBackend;
use Sabre\DAV\Auth\Plugin as AuthPlugin;
use Sabre\DAV\Server;
use Sabre\DAV\SimpleCollection;

final readonly class WebDavServer
{
    public function __construct(
        private BasicAuthBackend $authBackend,
        private string $baseUri = '/webdav/',
    ) {
    }

    public function create(): Server
    {
        $root = new SimpleCollection('root');
        $server = new Server($root);

        $server->setBaseUri($this->baseUri);
        $server->addPlugin(new AuthPlugin($this->authBackend));

        return $server;
    }
}