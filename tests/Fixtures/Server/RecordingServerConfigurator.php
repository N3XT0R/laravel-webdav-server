<?php

declare(strict_types=1);

namespace N3XT0R\LaravelWebdavServer\Tests\Fixtures\Server;

use N3XT0R\LaravelWebdavServer\Contracts\Server\ServerConfiguratorInterface;
use Sabre\DAV\Server;

final class RecordingServerConfigurator implements ServerConfiguratorInterface
{
    /** @var list<array{server:Server,spaceKey:string}> */
    public array $calls = [];

    public function configure(Server $server, string $spaceKey): void
    {
        $this->calls[] = [
            'server' => $server,
            'spaceKey' => $spaceKey,
        ];
    }
}
