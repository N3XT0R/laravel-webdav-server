<?php

declare(strict_types=1);

namespace N3XT0R\LaravelWebdavServer\Contracts\Server;

use N3XT0R\LaravelWebdavServer\Exception\Auth\AuthException;
use N3XT0R\LaravelWebdavServer\ValueObjects\WebDavPrincipalValueObject;

interface PrincipalAuthenticatorInterface
{
    /**
     * Authenticate raw credentials and return the resolved WebDAV principal.
     *
     * @param  string  $username  Username extracted from the incoming request.
     * @param  string  $password  Plain-text password extracted from the incoming request.
     * @return WebDavPrincipalValueObject Authenticated principal for the request.
     *
     * @throws AuthException When authentication fails.
     */
    public function authenticate(string $username, string $password): WebDavPrincipalValueObject;
}
