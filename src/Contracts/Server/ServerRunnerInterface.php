<?php

declare(strict_types=1);

namespace N3XT0R\LaravelWebdavServer\Contracts\Server;

use Sabre\DAV\Server;
use Symfony\Component\HttpFoundation\Response;

interface ServerRunnerInterface
{
    /**
     * Hand off execution to the configured WebDAV runtime boundary.
     *
     * @param \Sabre\DAV\Server $server Fully prepared SabreDAV server instance.
     *
     * @return \Symfony\Component\HttpFoundation\Response Response object returned by the runtime adapter. Implementations may terminate the request lifecycle.
     */
    public function run(Server $server): Response;
}
