<?php

declare(strict_types=1);

namespace N3XT0R\LaravelWebdavServer\Contracts;

use N3XT0R\LaravelWebdavServer\ValueObjects\WebDavPrincipal;

interface CredentialValidatorInterface
{
    /**
     * Validate the given credentials and return a principal on success.
     *
     * Return null if authentication fails.
     */
    public function validate(string $username, string $password): ?WebDavPrincipal;
}