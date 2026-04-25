<?php

declare(strict_types=1);

namespace N3XT0R\LaravelWebdavServer\Contracts\Server;

use Illuminate\Http\Request;
use N3XT0R\LaravelWebdavServer\Exception\Storage\InvalidDefaultSpaceConfigurationException;

interface SpaceKeyResolverInterface
{
    /**
     * Resolve the logical storage space key for the incoming request.
     *
     * @param  Request  $request  Incoming HTTP request targeting the WebDAV endpoint.
     * @return string Logical space key used for downstream storage resolution.
     *
     * @throws InvalidDefaultSpaceConfigurationException When no valid fallback space is configured.
     */
    public function resolve(Request $request): string;
}
