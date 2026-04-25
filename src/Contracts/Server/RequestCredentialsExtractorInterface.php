<?php

declare(strict_types=1);

namespace N3XT0R\LaravelWebdavServer\Contracts\Server;

use Illuminate\Http\Request;

interface RequestCredentialsExtractorInterface
{
    /**
     * Extract Basic Auth credentials from the incoming request.
     *
     * @param \Illuminate\Http\Request $request Incoming HTTP request targeting the WebDAV endpoint.
     *
     * @throws \N3XT0R\LaravelWebdavServer\Exception\Auth\MissingCredentialsException When no Basic Auth credentials are present.
     * @throws \N3XT0R\LaravelWebdavServer\Exception\Auth\InvalidCredentialsException When the Basic Auth payload is malformed or incomplete.
     *
     * @return array{0:string,1:string} Tuple of `[username, password]` extracted from the request.
     */
    public function extract(Request $request): array;
}
