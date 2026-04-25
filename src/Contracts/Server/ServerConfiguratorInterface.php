<?php

declare(strict_types=1);

namespace N3XT0R\LaravelWebdavServer\Contracts\Server;

use Sabre\DAV\Server;

interface ServerConfiguratorInterface
{
    /**
     * Apply package-specific runtime configuration to the prepared SabreDAV server.
     *
     * @param  Server  $server  Prepared SabreDAV server instance for the current request.
     * @param  string  $spaceKey  Logical storage space key resolved from the request.
     */
    public function configure(Server $server, string $spaceKey): void;
}
