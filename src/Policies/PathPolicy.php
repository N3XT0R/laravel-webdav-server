<?php

declare(strict_types=1);

namespace N3XT0R\LaravelWebdavServer\Policies;

use Illuminate\Contracts\Auth\Authenticatable;
use N3XT0R\LaravelWebdavServer\DTO\Auth\PathResourceDto;

final class PathPolicy
{
    /**
     * Determines whether the authenticated user may read the requested WebDAV path.
     *
     * @param Authenticatable $user Authenticated Laravel user that performs the WebDAV request.
     * @param PathResourceDto $resource Target storage resource with the resolved disk and relative path.
     * @return bool `true` when the user may read the resource; otherwise `false`.
     */
    public function read(Authenticatable $user, PathResourceDto $resource): bool
    {
        return $this->isAllowed($user, $resource);
    }

    /**
     * Determines whether the authenticated user may overwrite an existing file or directory path.
     *
     * @param Authenticatable $user Authenticated Laravel user that performs the WebDAV request.
     * @param PathResourceDto $resource Target storage resource with the resolved disk and relative path.
     * @return bool `true` when the user may write to the resource; otherwise `false`.
     */
    public function write(Authenticatable $user, PathResourceDto $resource): bool
    {
        return $this->isAllowed($user, $resource);
    }

    /**
     * Determines whether the authenticated user may delete the requested file or directory path.
     *
     * @param Authenticatable $user Authenticated Laravel user that performs the WebDAV request.
     * @param PathResourceDto $resource Target storage resource with the resolved disk and relative path.
     * @return bool `true` when the user may delete the resource; otherwise `false`.
     */
    public function delete(Authenticatable $user, PathResourceDto $resource): bool
    {
        return $this->isAllowed($user, $resource);
    }

    /**
     * Determines whether the authenticated user may create a directory at the requested path.
     *
     * @param Authenticatable $user Authenticated Laravel user that performs the WebDAV request.
     * @param PathResourceDto $resource Target storage resource with the resolved disk and relative path.
     * @return bool `true` when the user may create the directory; otherwise `false`.
     */
    public function createDirectory(Authenticatable $user, PathResourceDto $resource): bool
    {
        return $this->isAllowed($user, $resource);
    }

    /**
     * Determines whether the authenticated user may create a file at the requested path.
     *
     * @param Authenticatable $user Authenticated Laravel user that performs the WebDAV request.
     * @param PathResourceDto $resource Target storage resource with the resolved disk and relative path.
     * @return bool `true` when the user may create the file; otherwise `false`.
     */
    public function createFile(Authenticatable $user, PathResourceDto $resource): bool
    {
        return $this->isAllowed($user, $resource);
    }

    private function isAllowed(Authenticatable $user, PathResourceDto $resource): bool
    {
        $path = trim($resource->path, '/');

        foreach ($this->configuredUserRootsFor($user) as $configuredRoot) {
            if ($resource->disk !== $configuredRoot['disk']) {
                continue;
            }

            if ($path === $configuredRoot['path'] || str_starts_with($path, $configuredRoot['path'].'/')) {
                return true;
            }
        }

        return false;
    }

    /**
     * @return list<array{disk:string,path:string}>
     */
    private function configuredUserRootsFor(Authenticatable $user): array
    {
        $spaces = config('webdav-server.storage.spaces', []);

        if (! is_array($spaces)) {
            return [];
        }

        $roots = [];

        foreach ($spaces as $space) {
            if (! is_array($space)) {
                continue;
            }

            $disk = $space['disk'] ?? null;
            $root = $space['root'] ?? null;
            $prefix = $space['prefix'] ?? null;

            if (! is_string($disk) || trim($disk) === '') {
                continue;
            }

            if (! is_string($root) || trim($root) === '') {
                continue;
            }

            $parts = [trim($root, '/')];

            if (is_string($prefix) && trim($prefix) !== '' && trim($prefix) !== '/') {
                $parts[] = trim($prefix, '/');
            }

            $parts[] = (string) $user->getAuthIdentifier();

            $roots[] = [
                'disk' => trim($disk),
                'path' => implode('/', $parts),
            ];
        }

        return $roots;
    }
}
