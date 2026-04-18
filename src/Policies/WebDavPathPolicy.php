<?php

declare(strict_types=1);

namespace N3XT0R\LaravelWebdavServer\Policies;

use Illuminate\Contracts\Auth\Authenticatable;
use N3XT0R\LaravelWebdavServer\DTO\Auth\WebDavPathResourceDto;

final class WebDavPathPolicy
{
    public function read(Authenticatable $user, WebDavPathResourceDto $resource): bool
    {
        return $this->isAllowed($user, $resource);
    }

    public function write(Authenticatable $user, WebDavPathResourceDto $resource): bool
    {
        return $this->isAllowed($user, $resource);
    }

    public function delete(Authenticatable $user, WebDavPathResourceDto $resource): bool
    {
        return $this->isAllowed($user, $resource);
    }

    public function createDirectory(Authenticatable $user, WebDavPathResourceDto $resource): bool
    {
        return $this->isAllowed($user, $resource);
    }

    public function createFile(Authenticatable $user, WebDavPathResourceDto $resource): bool
    {
        return $this->isAllowed($user, $resource);
    }

    private function isAllowed(Authenticatable $user, WebDavPathResourceDto $resource): bool
    {
        $expectedDisk = (string)config('webdav-server.storage.disk', 'local');
        $path = trim($resource->path, '/');

        if ($resource->disk !== $expectedDisk) {
            return false;
        }

        $userRoot = (string)$user->getAuthIdentifier();

        return $path === $userRoot || str_starts_with($path, $userRoot.'/');
    }
}
