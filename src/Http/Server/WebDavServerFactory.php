<?php

declare(strict_types=1);

namespace N3XT0R\LaravelWebdavServer\Http\Server;

use Illuminate\Contracts\Container\Container;

readonly class WebDavServerFactory
{
    public function __construct(
        private Container $container,
    ) {}

    public function make(): WebDavServer
    {
        return new WebDavServer;
    }
}
