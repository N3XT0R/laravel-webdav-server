<?php

declare(strict_types=1);

namespace N3XT0R\LaravelWebdavServer\Contracts\Auth;

use N3XT0R\LaravelWebdavServer\ValueObjects\WebDavPrincipal;

interface PathAuthorizationInterface
{
    public function authorizeRead(WebDavPrincipal $principal, string $disk, string $path): void;

    public function authorizeWrite(WebDavPrincipal $principal, string $disk, string $path): void;

    public function authorizeDelete(WebDavPrincipal $principal, string $disk, string $path): void;

    public function authorizeCreateDirectory(WebDavPrincipal $principal, string $disk, string $path): void;

    public function authorizeCreateFile(WebDavPrincipal $principal, string $disk, string $path): void;
}