<?php

declare(strict_types=1);

namespace N3XT0R\LaravelWebdavServer\Auth;

use N3XT0R\LaravelWebdavServer\Contracts\Auth\CredentialValidatorInterface;
use N3XT0R\LaravelWebdavServer\ValueObjects\WebDavPrincipal;
use Sabre\DAV\Auth\Backend\AbstractBasic;

class BasicAuthBackend extends AbstractBasic
{
    protected ?WebDavPrincipal $principal = null;

    public function __construct(
        protected readonly CredentialValidatorInterface $validator,
        protected $realm = 'Laravel WebDAV',
    ) {
    }

    protected function validateUserPass($username, $password): bool
    {
        $principal = $this->validator->validate((string)$username, (string)$password);

        if (!$principal) {
            return false;
        }

        $this->principal = $principal;

        return true;
    }

    /**
     * Expose authenticated principal for later use (e.g. space resolver).
     */
    public function getPrincipal(): ?WebDavPrincipal
    {
        return $this->principal;
    }

    public function getRealm(): string
    {
        return $this->realm;
    }
}
