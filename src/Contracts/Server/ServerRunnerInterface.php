<?php

declare(strict_types=1);

namespace N3XT0R\LaravelWebdavServer\Contracts\Server;

use Sabre\DAV\Server;
use Symfony\Component\HttpFoundation\Response;

interface ServerRunnerInterface
{
    public function run(Server $server): Response;
}
