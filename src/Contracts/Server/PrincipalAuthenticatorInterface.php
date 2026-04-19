<?php

declare(strict_types=1);

namespace N3XT0R\LaravelWebdavServer\Contracts\Server;

use N3XT0R\LaravelWebdavServer\ValueObjects\WebDavPrincipal;

interface PrincipalAuthenticatorInterface
{
    public function authenticate(string $username, string $password): WebDavPrincipal;
}

