<?php

declare(strict_types=1);

namespace N3XT0R\LaravelWebdavServer\Nodes;

use N3XT0R\LaravelWebdavServer\DTO\Storage\StorageNodeContextDto;
use Sabre\DAV\Collection;
use Sabre\DAV\Exception\NotFound;
use Sabre\DAV\INode;

abstract class AbstractStorageCollection extends Collection
{
    public function __construct(
        protected readonly string $name,
        protected readonly string $path,
        protected readonly StorageNodeContextDto $context,
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
        $this->context->authorization->authorizeRead(
            $this->context->principal,
            $this->context->disk,
            $this->path,
        );

        $fs = $this->context->filesystem;

        if (! $fs->exists($this->path)) {
            return [];
        }

        $children = [];

        foreach ($fs->directories($this->path) as $directory) {
            $children[] = new StorageDirectory(
                name: basename($directory),
                path: $directory,
                context: $this->context,
            );
        }

        foreach ($fs->files($this->path) as $file) {
            $children[] = new StorageFile(
                name: basename($file),
                path: $file,
                context: $this->context,
            );
        }

        return $children;
    }

    public function getChild($name): INode
    {
        $path = $this->buildChildPath((string) $name);

        $this->context->authorization->authorizeRead(
            $this->context->principal,
            $this->context->disk,
            $path,
        );

        $fs = $this->context->filesystem;

        if (! $fs->exists($path)) {
            throw new NotFound("Node '{$name}' not found.");
        }

        if ($this->isDirectory($path)) {
            return new StorageDirectory(
                name: (string) $name,
                path: $path,
                context: $this->context,
            );
        }

        return new StorageFile(
            name: (string) $name,
            path: $path,
            context: $this->context,
        );
    }

    public function childExists($name): bool
    {
        $path = $this->buildChildPath((string) $name);

        try {
            $this->context->authorization->authorizeRead(
                $this->context->principal,
                $this->context->disk,
                $path,
            );
        } catch (\Throwable) {
            return false;
        }

        return $this->context->filesystem->exists($path);
    }

    public function createDirectory($name): void
    {
        $path = $this->buildChildPath((string) $name);

        $this->context->authorization->authorizeCreateDirectory(
            $this->context->principal,
            $this->context->disk,
            $path,
        );

        $this->context->filesystem->makeDirectory($path);
    }

    public function createFile($name, $data = null): void
    {
        $path = $this->buildChildPath((string) $name);

        $this->context->authorization->authorizeCreateFile(
            $this->context->principal,
            $this->context->disk,
            $path,
        );

        $fs = $this->context->filesystem;

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
        return trim($this->path, '/').'/'.ltrim($name, '/');
    }

    private function isDirectory(string $path): bool
    {
        $fs = $this->context->filesystem;
        $parent = dirname($path);

        return in_array($path, $fs->directories($parent), true);
    }
}
