<?php

declare(strict_types=1);

namespace App\Policies;

use Illuminate\Contracts\Auth\Authenticatable;
use N3XT0R\LaravelWebdavServer\DTO\Auth\WebDavPathResourceDto;

final class WebDavPathPolicy
{
    public function read(Authenticatable $user, WebDavPathResourceDto $resource): bool
    {
        return $this->isWithinUserRoot($user, $resource);
    }

    public function write(Authenticatable $user, WebDavPathResourceDto $resource): bool
    {
        return $this->isWithinUserRoot($user, $resource);
    }

    public function delete(Authenticatable $user, WebDavPathResourceDto $resource): bool
    {
        return $this->isWithinUserRoot($user, $resource);
    }

    public function createDirectory(Authenticatable $user, WebDavPathResourceDto $resource): bool
    {
        return $this->isWithinUserRoot($user, $resource);
    }

    public function createFile(Authenticatable $user, WebDavPathResourceDto $resource): bool
    {
        return $this->isWithinUserRoot($user, $resource);
    }

    private function isWithinUserRoot(Authenticatable $user, WebDavPathResourceDto $resource): bool
    {
        $prefix = trim((string)config('webdav.storage.prefix', 'webdav'), '/');
        $path = trim($resource->path, '/');

        $userRoot = $prefix.'/'.$user->getAuthIdentifier();

        return $path === $userRoot || str_starts_with($path, $userRoot.'/');
    }
}