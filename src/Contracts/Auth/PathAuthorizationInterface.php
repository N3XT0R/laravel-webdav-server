<?php

declare(strict_types=1);

namespace N3XT0R\LaravelWebdavServer\Contracts\Auth;

use N3XT0R\LaravelWebdavServer\ValueObjects\WebDavPrincipalValueObject;

interface PathAuthorizationInterface
{
    public function authorizeRead(WebDavPrincipalValueObject $principal, string $disk, string $path): void;

    public function authorizeWrite(WebDavPrincipalValueObject $principal, string $disk, string $path): void;

    public function authorizeDelete(WebDavPrincipalValueObject $principal, string $disk, string $path): void;

    public function authorizeCreateDirectory(WebDavPrincipalValueObject $principal, string $disk, string $path): void;

    public function authorizeCreateFile(WebDavPrincipalValueObject $principal, string $disk, string $path): void;
}
