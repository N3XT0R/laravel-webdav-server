<?php

declare(strict_types=1);

namespace N3XT0R\LaravelWebdavServer\Tests\Fixtures\Auth;

use N3XT0R\LaravelWebdavServer\Contracts\Auth\PathAuthorizationInterface;
use N3XT0R\LaravelWebdavServer\ValueObjects\WebDavPrincipalValueObject;

class AllowAllPathAuthorization implements PathAuthorizationInterface
{
    /** @var list<array{ability:string,disk:string,path:string,principalId:string}> */
    public array $calls = [];

    public function authorizeRead(WebDavPrincipalValueObject $principal, string $disk, string $path): void
    {
        $this->record('read', $principal, $disk, $path);
    }

    public function authorizeWrite(WebDavPrincipalValueObject $principal, string $disk, string $path): void
    {
        $this->record('write', $principal, $disk, $path);
    }

    public function authorizeDelete(WebDavPrincipalValueObject $principal, string $disk, string $path): void
    {
        $this->record('delete', $principal, $disk, $path);
    }

    public function authorizeCreateDirectory(WebDavPrincipalValueObject $principal, string $disk, string $path): void
    {
        $this->record('createDirectory', $principal, $disk, $path);
    }

    public function authorizeCreateFile(WebDavPrincipalValueObject $principal, string $disk, string $path): void
    {
        $this->record('createFile', $principal, $disk, $path);
    }

    private function record(string $ability, WebDavPrincipalValueObject $principal, string $disk, string $path): void
    {
        $this->calls[] = [
            'ability' => $ability,
            'disk' => $disk,
            'path' => $path,
            'principalId' => $principal->id,
        ];
    }
}
