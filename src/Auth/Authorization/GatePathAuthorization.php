<?php

declare(strict_types=1);

namespace N3XT0R\LaravelWebdavServer\Auth\Authorization;

use Illuminate\Contracts\Auth\Access\Gate;
use N3XT0R\LaravelWebdavServer\Auth\Data\WebDavPathResource;
use N3XT0R\LaravelWebdavServer\Contracts\Auth\PathAuthorizationInterface;
use N3XT0R\LaravelWebdavServer\DTO\Auth\WebDavPathResourceDto;
use N3XT0R\LaravelWebdavServer\ValueObjects\WebDavPrincipal;
use Sabre\DAV\Exception\Forbidden;

final class GatePathAuthorization implements PathAuthorizationInterface
{
    public function __construct(
        private readonly Gate $gate,
    ) {
    }

    public function authorizeRead(WebDavPrincipal $principal, string $disk, string $path): void
    {
        $this->authorize($principal, 'read', $disk, $path);
    }

    public function authorizeWrite(WebDavPrincipal $principal, string $disk, string $path): void
    {
        $this->authorize($principal, 'write', $disk, $path);
    }

    public function authorizeDelete(WebDavPrincipal $principal, string $disk, string $path): void
    {
        $this->authorize($principal, 'delete', $disk, $path);
    }

    public function authorizeCreateDirectory(WebDavPrincipal $principal, string $disk, string $path): void
    {
        $this->authorize($principal, 'createDirectory', $disk, $path);
    }

    public function authorizeCreateFile(WebDavPrincipal $principal, string $disk, string $path): void
    {
        $this->authorize($principal, 'createFile', $disk, $path);
    }

    private function authorize(WebDavPrincipal $principal, string $ability, string $disk, string $path): void
    {
        $resource = new WebDavPathResourceDto(
            disk: $disk,
            path: $path,
        );

        $response = $this->gate->forUser($principal->user)->inspect($ability, $resource);

        if (!$response->allowed()) {
            throw new Forbidden($response->message() ?: 'Access denied.');
        }
    }
}