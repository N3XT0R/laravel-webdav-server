<?php

declare(strict_types=1);

namespace N3XT0R\LaravelWebdavServer\Contracts\Server;

use Illuminate\Http\Request;
use N3XT0R\LaravelWebdavServer\DTO\Server\RequestContextDto;

interface RequestContextResolverInterface
{
    /**
     * Resolve the full request context required to build a WebDAV server for the incoming request.
     *
     * @param \Illuminate\Http\Request $request Incoming HTTP request targeting the WebDAV endpoint.
     *
     * @throws \N3XT0R\LaravelWebdavServer\Exception\DomainException When credentials, auth, or storage resolution fails.
     *
     * @return \N3XT0R\LaravelWebdavServer\DTO\Server\RequestContextDto Runtime context containing principal, space key, and resolved storage space.
     */
    public function resolve(Request $request): RequestContextDto;
}
