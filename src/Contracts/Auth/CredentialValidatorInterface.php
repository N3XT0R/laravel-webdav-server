<?php

declare(strict_types=1);

namespace N3XT0R\LaravelWebdavServer\Contracts\Auth;

use N3XT0R\LaravelWebdavServer\Exception\Auth\AuthException;
use N3XT0R\LaravelWebdavServer\ValueObjects\WebDavPrincipalValueObject;

interface CredentialValidatorInterface
{
    /**
     * Validate incoming Basic Auth credentials and resolve the authenticated WebDAV principal.
     *
     * @param  string  $username  Username extracted from the incoming Basic Auth credentials.
     * @param  string  $password  Plain-text password extracted from the incoming Basic Auth credentials.
     * @return WebDavPrincipalValueObject Authenticated principal resolved from the credentials.
     *
     * @throws AuthException When credentials are missing, invalid, or cannot be resolved.
     */
    public function validate(string $username, string $password): WebDavPrincipalValueObject;
}
