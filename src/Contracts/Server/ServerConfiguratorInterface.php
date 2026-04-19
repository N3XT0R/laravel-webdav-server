<?php

declare(strict_types=1);

namespace N3XT0R\LaravelWebdavServer\Contracts\Server;

use Sabre\DAV\Server;

interface ServerConfiguratorInterface
{
    public function configure(Server $server, string $spaceKey): void;
}

