<?php

declare(strict_types=1);

namespace N3XT0R\LaravelWebdavServer\Server;

use N3XT0R\LaravelWebdavServer\Auth\Backends\BasicAuthBackend;

final readonly class WebDavServerFactory
{
    public function __construct(
        private BasicAuthBackend $authBackend,
    ) {}

    public function make(): WebDavServer
    {
        return new WebDavServer(
            authBackend: $this->authBackend,
            baseUri: (string) config('webdav.base_uri', '/webdav/'),
        );
    }
}
