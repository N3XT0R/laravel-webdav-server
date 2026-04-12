<?php

declare(strict_types=1);

namespace N3XT0R\LaravelWebdavServer\Nodes;

use N3XT0R\LaravelWebdavServer\DTO\Storage\StorageNodeContextDto;
use Sabre\DAV\Collection;
use Sabre\DAV\Exception\NotFound;
use Sabre\DAV\INode;

final class StorageDirectory extends Collection
{
    public function __construct(
        private readonly string $name,
        private readonly string $disk,
        private readonly string $path,
        private readonly StorageNodeContextDto $context,
    ) {
    }

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
            $this->disk,
            $this->path,
        );

        $fs = $this->context->filesystem->disk($this->disk);

        if (!$fs->exists($this->path)) {
            return [];
        }

        $children = [];

        foreach ($fs->directories($this->path) as $directory) {
            $children[] = new self(
                name: basename($directory),
                disk: $this->disk,
                path: $directory,
                context: $this->context,
            );
        }

        foreach ($fs->files($this->path) as $file) {
            $children[] = new StorageFile(
                name: basename($file),
                disk: $this->disk,
                path: $file,
                context: $this->context,
            );
        }

        return $children;
    }

    public function getChild($name): INode
    {
        $path = $this->buildChildPath((string)$name);

        $this->context->authorization->authorizeRead(
            $this->context->principal,
            $this->disk,
            $path,
        );

        $fs = $this->context->filesystem->disk($this->disk);

        if (!$fs->exists($path)) {
            throw new NotFound("Node '{$name}' not found.");
        }

        if ($this->isDirectory($path)) {
            return new self(
                name: (string)$name,
                disk: $this->disk,
                path: $path,
                context: $this->context,
            );
        }

        return new StorageFile(
            name: (string)$name,
            disk: $this->disk,
            path: $path,
            context: $this->context,
        );
    }

    public function childExists($name): bool
    {
        $path = $this->buildChildPath((string)$name);

        try {
            $this->context->authorization->authorizeRead(
                $this->context->principal,
                $this->disk,
                $path,
            );
        } catch (\Throwable) {
            return false;
        }

        return $this->context->filesystem
            ->disk($this->disk)
            ->exists($path);
    }

    public function createDirectory($name): void
    {
        $path = $this->buildChildPath((string)$name);

        $this->context->authorization->authorizeCreateDirectory(
            $this->context->principal,
            $this->disk,
            $path,
        );

        $this->context->filesystem
            ->disk($this->disk)
            ->makeDirectory($path);
    }

    public function createFile($name, $data = null): void
    {
        $path = $this->buildChildPath((string)$name);

        $this->context->authorization->authorizeCreateFile(
            $this->context->principal,
            $this->disk,
            $path,
        );

        $fs = $this->context->filesystem->disk($this->disk);

        if (is_resource($data)) {
            $contents = stream_get_contents($data);

            if ($contents === false) {
                throw new \RuntimeException('Failed to read file stream.');
            }

            $fs->put($path, $contents);

            return;
        }

        $fs->put($path, (string)($data ?? ''));
    }

    public function delete(): void
    {
        $this->context->authorization->authorizeDelete(
            $this->context->principal,
            $this->disk,
            $this->path,
        );

        $fs = $this->context->filesystem->disk($this->disk);

        $this->deleteRecursively($fs, $this->path);
    }

    private function deleteRecursively(object $fs, string $path): void
    {
        foreach ($fs->files($path) as $file) {
            $this->context->authorization->authorizeDelete(
                $this->context->principal,
                $this->disk,
                $file,
            );

            $fs->delete($file);
        }

        foreach ($fs->directories($path) as $directory) {
            $this->context->authorization->authorizeDelete(
                $this->context->principal,
                $this->disk,
                $directory,
            );

            $this->deleteRecursively($fs, $directory);
        }

        $fs->deleteDirectory($path);
    }

    private function buildChildPath(string $name): string
    {
        return trim($this->path, '/').'/'.ltrim($name, '/');
    }

    private function isDirectory(string $path): bool
    {
        $fs = $this->context->filesystem->disk($this->disk);
        $parent = dirname($path);

        return in_array($path, $fs->directories($parent), true);
    }
}
