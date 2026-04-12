<?php

declare(strict_types=1);

namespace N3XT0R\LaravelWebdavServer\Nodes;

use Illuminate\Contracts\Filesystem\Factory as FilesystemManager;
use Sabre\DAV\Collection;
use Sabre\DAV\Exception\NotFound;
use Sabre\DAV\INode;

final class StorageDirectory extends Collection
{
    public function __construct(
        private readonly string $name,
        private readonly string $disk,
        private readonly string $path,
        private readonly FilesystemManager $filesystem,
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
        $fs = $this->filesystem->disk($this->disk);

        if (!$fs->exists($this->path)) {
            return [];
        }

        $children = [];

        foreach ($fs->directories($this->path) as $directory) {
            $children[] = new self(
                name: basename($directory),
                disk: $this->disk,
                path: $directory,
                filesystem: $this->filesystem,
            );
        }

        foreach ($fs->files($this->path) as $file) {
            $children[] = new StorageFile(
                name: basename($file),
                disk: $this->disk,
                path: $file,
                filesystem: $this->filesystem,
            );
        }

        return $children;
    }

    public function getChild($name): INode
    {
        $path = $this->buildChildPath((string)$name);
        $fs = $this->filesystem->disk($this->disk);

        if (!$fs->exists($path)) {
            throw new NotFound("Node '{$name}' not found.");
        }

        if ($this->isDirectory($path)) {
            return new self(
                name: (string)$name,
                disk: $this->disk,
                path: $path,
                filesystem: $this->filesystem,
            );
        }

        return new StorageFile(
            name: (string)$name,
            disk: $this->disk,
            path: $path,
            filesystem: $this->filesystem,
        );
    }

    public function childExists($name): bool
    {
        return $this->filesystem
            ->disk($this->disk)
            ->exists($this->buildChildPath((string)$name));
    }

    public function createDirectory($name): void
    {
        $this->filesystem
            ->disk($this->disk)
            ->makeDirectory($this->buildChildPath((string)$name));
    }

    public function createFile($name, $data = null): void
    {
        $path = $this->buildChildPath((string)$name);
        $fs = $this->filesystem->disk($this->disk);

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
        $fs = $this->filesystem->disk($this->disk);

        $this->deleteRecursively($fs, $this->path);
    }

    private function deleteRecursively(object $fs, string $path): void
    {
        foreach ($fs->files($path) as $file) {
            $fs->delete($file);
        }

        foreach ($fs->directories($path) as $directory) {
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
        $fs = $this->filesystem->disk($this->disk);
        $parent = dirname($path);

        return in_array($path, $fs->directories($parent), true);
    }
}