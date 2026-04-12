<?php

declare(strict_types=1);

namespace N3XT0R\LaravelWebdavServer\Nodes;

use Illuminate\Contracts\Filesystem\Factory as FilesystemManager;
use N3XT0R\LaravelWebdavServer\Contracts\Auth\PathAuthorizationInterface;
use N3XT0R\LaravelWebdavServer\ValueObjects\WebDavPrincipal;
use Sabre\DAV\Collection;
use Sabre\DAV\Exception\NotFound;
use Sabre\DAV\INode;

final class StorageRootCollection extends Collection
{
    public function __construct(
        private readonly string $name,
        private readonly string $disk,
        private readonly string $rootPath,
        private readonly FilesystemManager $filesystem,
        private readonly WebDavPrincipal $principal,
        private readonly PathAuthorizationInterface $authorization,
    ) {}

    public function getName(): string
    {
        return $this->name;
    }

    /**
     * @return list<INode>
     */
    public function getChildren(): array
    {
        $this->authorization->authorizeRead(
            $this->principal,
            $this->disk,
            $this->rootPath,
        );

        $fs = $this->filesystem->disk($this->disk);

        if (! $fs->exists($this->rootPath)) {
            return [];
        }

        $children = [];

        foreach ($fs->directories($this->rootPath) as $directory) {
            $children[] = new StorageDirectory(
                name: basename($directory),
                disk: $this->disk,
                path: $directory,
                filesystem: $this->filesystem,
                principal: $this->principal,
                authorization: $this->authorization,
            );
        }

        foreach ($fs->files($this->rootPath) as $file) {
            $children[] = new StorageFile(
                name: basename($file),
                disk: $this->disk,
                path: $file,
                filesystem: $this->filesystem,
                principal: $this->principal,
                authorization: $this->authorization,
            );
        }

        return $children;
    }

    public function getChild($name): INode
    {
        $path = $this->buildChildPath((string) $name);

        $this->authorization->authorizeRead(
            $this->principal,
            $this->disk,
            $path,
        );

        $fs = $this->filesystem->disk($this->disk);

        if (! $fs->exists($path)) {
            throw new NotFound("Node '{$name}' not found.");
        }

        if ($this->isDirectory($path)) {
            return new StorageDirectory(
                name: (string) $name,
                disk: $this->disk,
                path: $path,
                filesystem: $this->filesystem,
                principal: $this->principal,
                authorization: $this->authorization,
            );
        }

        return new StorageFile(
            name: (string) $name,
            disk: $this->disk,
            path: $path,
            filesystem: $this->filesystem,
            principal: $this->principal,
            authorization: $this->authorization,
        );
    }

    public function childExists($name): bool
    {
        $path = $this->buildChildPath((string) $name);

        try {
            $this->authorization->authorizeRead(
                $this->principal,
                $this->disk,
                $path,
            );
        } catch (\Throwable) {
            return false;
        }

        return $this->filesystem
            ->disk($this->disk)
            ->exists($path);
    }

    public function createDirectory($name): void
    {
        $path = $this->buildChildPath((string) $name);

        $this->authorization->authorizeCreateDirectory(
            $this->principal,
            $this->disk,
            $path,
        );

        $this->filesystem
            ->disk($this->disk)
            ->makeDirectory($path);
    }

    public function createFile($name, $data = null): void
    {
        $path = $this->buildChildPath((string) $name);

        $this->authorization->authorizeCreateFile(
            $this->principal,
            $this->disk,
            $path,
        );

        $fs = $this->filesystem->disk($this->disk);

        if (is_resource($data)) {
            $contents = stream_get_contents($data);

            if ($contents === false) {
                throw new \RuntimeException('Failed to read file stream.');
            }

            $fs->put($path, $contents);

            return;
        }

        $fs->put($path, (string) ($data ?? ''));
    }

    private function buildChildPath(string $name): string
    {
        return trim($this->rootPath, '/').'/'.ltrim($name, '/');
    }

    private function isDirectory(string $path): bool
    {
        $fs = $this->filesystem->disk($this->disk);
        $parent = dirname($path);

        return in_array($path, $fs->directories($parent), true);
    }
}
