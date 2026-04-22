<?php

declare(strict_types=1);

namespace N3XT0R\LaravelWebdavServer\Auth\Authorization;

use Illuminate\Contracts\Auth\Access\Gate;
use Illuminate\Contracts\Auth\Authenticatable;
use N3XT0R\LaravelWebdavServer\Contracts\Auth\PathAuthorizationInterface;
use N3XT0R\LaravelWebdavServer\DTO\Auth\WebDavPathResourceDto;
use N3XT0R\LaravelWebdavServer\ValueObjects\WebDavPrincipal;
use Sabre\DAV\Exception\Forbidden;

final readonly class GatePathAuthorization implements PathAuthorizationInterface
{
    public function __construct(
        private Gate $gate,
    ) {}

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
        logger()->error('GatePathAuthorization@authorize called', [
            'ability' => $ability,
            'disk' => $disk,
            'path' => $path,
            'user_class' => get_debug_type($principal->user),
            'user_id' => $principal->user instanceof Authenticatable
                ? $principal->user->getAuthIdentifier()
                : null,
        ]);
        $resource = new WebDavPathResourceDto(
            disk: $disk,
            path: $path,
        );

        $response = $this->gate->forUser($principal->user)->inspect($ability, $resource);

        if (! $response->allowed()) {
            throw new Forbidden($response->message() ?: 'Access denied.');
        }
    }
}
