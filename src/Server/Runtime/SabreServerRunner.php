<?php

declare(strict_types=1);

namespace N3XT0R\LaravelWebdavServer\Server\Runtime;

use Closure;
use N3XT0R\LaravelWebdavServer\Contracts\Server\ServerRunnerInterface;
use Sabre\DAV\Server;
use Symfony\Component\HttpFoundation\Response;

final readonly class SabreServerRunner implements ServerRunnerInterface
{
    private Closure $terminator;

    public function __construct(?Closure $terminator = null)
    {
        $this->terminator = $terminator ?? $this->terminateProcess(...);
    }

    /**
     * Start the SabreDAV runtime and terminate the current request lifecycle.
     *
     * @param  Server  $server  Fully prepared SabreDAV server instance.
     * @return Response This method never returns control to Laravel in normal runtime flow because SabreDAV takes over and exits.
     */
    public function run(Server $server): Response
    {
        $server->start();

        ($this->terminator)();
    }

    /**
     * @codeCoverageIgnore
     */
    private function terminateProcess(): never
    {
        exit;
    }
}
