<?php

declare(strict_types=1);

namespace N3XT0R\LaravelWebdavServer\Server\Configuration;

use N3XT0R\LaravelWebdavServer\Contracts\Server\ServerConfiguratorInterface;
use Sabre\DAV\Server;

final readonly class SabreServerConfigurator implements ServerConfiguratorInterface
{
    public function configure(Server $server, string $spaceKey): void
    {
        $baseUri = trim((string) config('webdav-server.base_uri', '/webdav/'), '/');
        $space = trim($spaceKey, '/');

        $server->setBaseUri('/'.$baseUri.'/'.$space.'/');
        $server->setLogger(app('log'));
    }
}
