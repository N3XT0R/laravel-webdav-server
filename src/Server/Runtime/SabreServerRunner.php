<?php

declare(strict_types=1);

namespace N3XT0R\LaravelWebdavServer\Server\Runtime;

use N3XT0R\LaravelWebdavServer\Contracts\Server\ServerRunnerInterface;
use Sabre\DAV\Server;
use Symfony\Component\HttpFoundation\Response;

final readonly class SabreServerRunner implements ServerRunnerInterface
{
    public function run(Server $server): Response
    {
        $server->start();

        exit;
    }
}

