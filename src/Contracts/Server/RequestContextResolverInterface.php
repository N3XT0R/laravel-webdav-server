<?php

declare(strict_types=1);

namespace N3XT0R\LaravelWebdavServer\Contracts\Server;

use Illuminate\Http\Request;
use N3XT0R\LaravelWebdavServer\DTO\Server\RequestContextDto;
use N3XT0R\LaravelWebdavServer\Exception\DomainException;

interface RequestContextResolverInterface
{
    /**
     * Resolve the full request context required to build a WebDAV server for the incoming request.
     *
     * @param  Request  $request  Incoming HTTP request targeting the WebDAV endpoint.
     * @return RequestContextDto Runtime context containing principal, space key, and resolved storage space.
     *
     * @throws DomainException When credentials, auth, or storage resolution fails.
     */
    public function resolve(Request $request): RequestContextDto;
}
