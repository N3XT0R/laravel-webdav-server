<?php

declare(strict_types=1);

namespace App\Policies;

use Illuminate\Auth\Authenticatable;
use N3XT0R\LaravelWebdavServer\DTO\Auth\WebDavPathResourceDto;

final class WebDavPathPolicy
{
    public function read(Authenticatable $user, WebDavPathResourceDto $resource): bool
    {
        return str_starts_with($resource->path, 'webdav/'.$user->id.'/')
            || $resource->path === 'webdav/'.$user->id;
    }

    public function write(Authenticatable $user, WebDavPathResourceDto $resource): bool
    {
        return str_starts_with($resource->path, 'webdav/'.$user->id.'/')
            || $resource->path === 'webdav/'.$user->id;
    }

    public function delete(Authenticatable $user, WebDavPathResourceDto $resource): bool
    {
        return str_starts_with($resource->path, 'webdav/'.$user->id.'/')
            || $resource->path === 'webdav/'.$user->id;
    }

    public function createDirectory(Authenticatable $user, WebDavPathResourceDto $resource): bool
    {
        return str_starts_with($resource->path, 'webdav/'.$user->id.'/')
            || $resource->path === 'webdav/'.$user->id;
    }

    public function createFile(Authenticatable $user, WebDavPathResourceDto $resource): bool
    {
        return str_starts_with($resource->path, 'webdav/'.$user->id.'/')
            || $resource->path === 'webdav/'.$user->id;
    }
}