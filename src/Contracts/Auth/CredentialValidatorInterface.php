<?php

declare(strict_types=1);

namespace N3XT0R\LaravelWebdavServer\Contracts\Auth;

use N3XT0R\LaravelWebdavServer\ValueObjects\WebDavPrincipalValueObject;

interface CredentialValidatorInterface
{
    public function validate(string $username, string $password): WebDavPrincipalValueObject;
}
