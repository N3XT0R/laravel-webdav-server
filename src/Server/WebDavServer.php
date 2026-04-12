<?php

declare(strict_types=1);

namespace N3XT0R\LaravelWebdavServer\Server;

use N3XT0R\LaravelWebdavServer\Auth\Backends\BasicAuthBackend;
use Sabre\DAV\Auth\Plugin as AuthPlugin;
use Sabre\DAV\Server;
use Sabre\DAV\SimpleCollection;
use N3XT0R\LaravelWebdavServer\Contracts\Storage\SpaceResolverInterface;
use N3XT0R\LaravelWebdavServer\Nodes\StorageRootCollection;

final readonly class WebDavServer
{
    public function __construct(
        protected BasicAuthBackend $authBackend,
        protected SpaceResolverInterface $spaceResolver,
        protected string $baseUri = '/webdav/',
    ) {
    }

    public function create(): Server
    {
        $principal = $this->authBackend->getPrincipal();

        if ($principal === null) {
            throw new \RuntimeException('No authenticated WebDAV principal available.');
        }


        $space = $this->spaceResolver->resolve($principal);

        $root = new StorageRootCollection(
            name: $principal->id,
            disk: $space->disk,
            rootPath: $space->rootPath,
        );
        $server = new Server($root);

        $server->setBaseUri($this->baseUri);
        $server->addPlugin(new AuthPlugin($this->authBackend));

        return $server;
    }
}
