<?php

declare(strict_types=1);

namespace N3XT0R\LaravelWebdavServer\Contracts\Server;

use Illuminate\Http\Request;
use N3XT0R\LaravelWebdavServer\Exception\Auth\InvalidCredentialsException;
use N3XT0R\LaravelWebdavServer\Exception\Auth\MissingCredentialsException;

interface RequestCredentialsExtractorInterface
{
    /**
     * Extract Basic Auth credentials from the incoming request.
     *
     * @param  Request  $request  Incoming HTTP request targeting the WebDAV endpoint.
     * @return array{0:string,1:string} Tuple of `[username, password]` extracted from the request.
     *
     * @throws MissingCredentialsException When no Basic Auth credentials are present.
     * @throws InvalidCredentialsException When the Basic Auth payload is malformed or incomplete.
     */
    public function extract(Request $request): array;
}
