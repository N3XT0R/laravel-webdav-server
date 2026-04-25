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
     */
    public function getPrincipal(): WebDavPrincipalValueObject
    {
        if ($this->principal instanceof WebDavPrincipalValueObject) {
            return $this->principal;
        }

        throw new UnauthenticatedPrincipalException('No principal is available before successful authentication.');
    }

    public function getRealm(): string
    {
        return $this->realm;
    }
}
