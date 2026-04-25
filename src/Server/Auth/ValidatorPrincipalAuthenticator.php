<?php

declare(strict_types=1);

namespace N3XT0R\LaravelWebdavServer\Server\Auth;

use N3XT0R\LaravelWebdavServer\Contracts\Auth\CredentialValidatorInterface;
use N3XT0R\LaravelWebdavServer\Contracts\Server\PrincipalAuthenticatorInterface;
use N3XT0R\LaravelWebdavServer\Exception\Auth\AuthException;
use N3XT0R\LaravelWebdavServer\ValueObjects\WebDavPrincipalValueObject;

final readonly class ValidatorPrincipalAuthenticator implements PrincipalAuthenticatorInterface
{
    /**
     * Create the default authenticator that delegates to a credential validator.
     *
     * @param  CredentialValidatorInterface  $validator  Credential validator used to resolve the authenticated principal.
     */
    public function __construct(
        private CredentialValidatorInterface $validator,
    ) {}

    /**
     * Authenticate raw credentials by delegating to the configured credential validator.
     *
     * @param  string  $username  Username extracted from the request.
     * @param  string  $password  Plain-text password extracted from the request.
     * @return WebDavPrincipalValueObject Authenticated principal for the request.
     *
     * @throws AuthException When authentication fails.
     */
    public function authenticate(string $username, string $password): WebDavPrincipalValueObject
    {
        return $this->validator->validate($username, $password);
    }
}
