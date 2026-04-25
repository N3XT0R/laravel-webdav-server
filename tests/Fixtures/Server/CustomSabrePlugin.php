<?php

declare(strict_types=1);

namespace N3XT0R\LaravelWebdavServer\Tests\Fixtures\Server;

use Sabre\DAV\Server;
use Sabre\DAV\ServerPlugin;

final class CustomSabrePlugin extends ServerPlugin
{
    public bool $initialized = false;

    public function initialize(Server $server): void
    {
        $this->initialized = true;
    }

    public function getPluginName(): string
    {
        return 'custom-sabre-plugin';
    }
}
