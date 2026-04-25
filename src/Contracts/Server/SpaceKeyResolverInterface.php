<?php

declare(strict_types=1);

namespace N3XT0R\LaravelWebdavServer\Contracts\Server;

use Illuminate\Http\Request;

interface SpaceKeyResolverInterface
{
    /**
     * Resolve the logical storage space key for the incoming request.
     *
     * @param \Illuminate\Http\Request $request Incoming HTTP request targeting the WebDAV endpoint.
     *
     * @throws \N3XT0R\LaravelWebdavServer\Exception\Storage\InvalidDefaultSpaceConfigurationException When no valid fallback space is configured.
     *
     * @return string Logical space key used for downstream storage resolution.
     */
    public function resolve(Request $request): string;
}
