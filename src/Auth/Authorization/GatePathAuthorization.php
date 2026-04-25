<?php

declare(strict_types=1);

namespace N3XT0R\LaravelWebdavServer\Auth\Authorization;

use Illuminate\Contracts\Auth\Access\Gate;
use N3XT0R\LaravelWebdavServer\Contracts\Auth\PathAuthorizationInterface;
use N3XT0R\LaravelWebdavServer\DTO\Auth\PathResourceDto;
use N3XT0R\LaravelWebdavServer\ValueObjects\WebDavPrincipalValueObject;
use Sabre\DAV\Exception\Forbidden;

final readonly class GatePathAuthorization implements PathAuthorizationInterface
{
    public function __construct(
        private Gate $gate,
    ) {}

    public function authorizeRead(WebDavPrincipalValueObject $principal, string $disk, string $path): void
    {
        $this->authorize($principal, 'read', $disk, $path);
    }

    public function authorizeWrite(WebDavPrincipalValueObject $principal, string $disk, string $path): void
    {
        $this->authorize($principal, 'write', $disk, $path);
    }

    public function authorizeDelete(WebDavPrincipalValueObject $principal, string $disk, string $path): void
    {
        $this->authorize($principal, 'delete', $disk, $path);
    }

    public function authorizeCreateDirectory(WebDavPrincipalValueObject $principal, string $disk, string $path): void
    {
        $this->authorize($principal, 'createDirectory', $disk, $path);
    }

    public function authorizeCreateFile(WebDavPrincipalValueObject $principal, string $disk, string $path): void
    {
        $this->authorize($principal, 'createFile', $disk, $path);
    }

    private function authorize(WebDavPrincipalValueObject $principal, string $ability, string $disk, string $path): void
    {
        $resource = new PathResourceDto(
            disk: $disk,
            path: $path,
        );

        $response = $this->gate->forUser($principal->user)->inspect($ability, $resource);

        if (! $response->allowed()) {
            throw new Forbidden($response->message() ?: 'Access denied.');
        }
    }
}
