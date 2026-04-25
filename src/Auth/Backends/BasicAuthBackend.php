<?php

declare(strict_types=1);

namespace N3XT0R\LaravelWebdavServer\Auth\Backends;

use N3XT0R\LaravelWebdavServer\Contracts\Auth\CredentialValidatorInterface;
use N3XT0R\LaravelWebdavServer\Exception\Auth\AuthException;
use N3XT0R\LaravelWebdavServer\Exception\Auth\UnauthenticatedPrincipalException;
use N3XT0R\LaravelWebdavServer\ValueObjects\WebDavPrincipalValueObject;
use Sabre\DAV\Auth\Backend\AbstractBasic;

class BasicAuthBackend extends AbstractBasic
{
    protected ?WebDavPrincipalValueObject $principal = null;

    /**
     * Create the SabreDAV Basic Auth backend adapter used at the protocol boundary.
     *
     * @param \N3XT0R\LaravelWebdavServer\Contracts\Auth\CredentialValidatorInterface $validator Credential validator used to authenticate the incoming username and password.
     * @param mixed $realm Realm string exposed in `WWW-Authenticate` responses. Kept untyped to match the SabreDAV parent property.
     */
    public function __construct(
        protected readonly CredentialValidatorInterface $validator,
        protected $realm = 'Laravel WebDAV',
    ) {}

    protected function validateUserPass($username, $password): bool
    {
        try {
            $principal = $this->validator->validate((string) $username, (string) $password);
        } catch (AuthException) {
            return false;
        }

        $this->principal = $principal;

        return true;
    }

    /**
     * Expose authenticated principal for later use (e.g. space resolver).
     *
     * @throws \N3XT0R\LaravelWebdavServer\Exception\Auth\UnauthenticatedPrincipalException When authentication has not yet succeeded.
     *
     * @return \N3XT0R\LaravelWebdavServer\ValueObjects\WebDavPrincipalValueObject Authenticated principal captured during successful Basic Auth validation.
     */
    public function getPrincipal(): WebDavPrincipalValueObject
    {
        if ($this->principal instanceof WebDavPrincipalValueObject) {
            return $this->principal;
        }

        throw new UnauthenticatedPrincipalException('No principal is available before successful authentication.');
    }

    /**
     * Return the HTTP Basic Auth realm used by SabreDAV challenges.
     *
     * @return string Realm string sent in `WWW-Authenticate` headers.
     */
    public function getRealm(): string
    {
        return $this->realm;
    }
}
