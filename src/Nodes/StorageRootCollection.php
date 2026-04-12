<?php

declare(strict_types=1);

namespace N3XT0R\LaravelWebdavServer\Nodes;

use Illuminate\Contracts\Filesystem\Factory as FilesystemManager;
use Sabre\DAV\Collection;
use Sabre\DAV\INode;

final class StorageRootCollection extends Collection
{
    public function __construct(
        private readonly string $name,
        private readonly string $disk,
        private readonly string $rootPath,
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

        if (!$fs->exists($this->rootPath)) {
            return [];
        }

        $directories = $fs->directories($this->rootPath);
        $files = $fs->files($this->rootPath);

        $children = [];

        foreach ($directories as $dir) {
            $children[] = new StorageDirectory(
                name: basename($dir),
                disk: $this->disk,
                path: $dir,
                filesystem: $this->filesystem,
            );
        }

        foreach ($files as $file) {
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
        $fs = $this->filesystem->disk($this->disk);

        $path = $this->rootPath.'/'.$name;

        if ($fs->exists($path)) {
            if ($this->isDirectory($path)) {
                return new StorageDirectory(
                    name: $name,
                    disk: $this->disk,
                    path: $path,
                    filesystem: $this->filesystem,
                );
            }

            return new StorageFile(
                name: $name,
                disk: $this->disk,
                path: $path,
                filesystem: $this->filesystem,
            );
        }

        throw new \Sabre\DAV\Exception\NotFound("Node '{$name}' not found");
    }

    public function childExists($name): bool
    {
        $fs = $this->filesystem->disk($this->disk);

        return $fs->exists($this->rootPath.'/'.$name);
    }

    public function createDirectory($name): void
    {
        $fs = $this->filesystem->disk($this->disk);

        $fs->makeDirectory($this->rootPath.'/'.$name);
    }

    public function createFile($name, $data = null): void
    {
        $fs = $this->filesystem->disk($this->disk);

        $path = $this->rootPath.'/'.$name;

        if (is_resource($data)) {
            $fs->put($path, stream_get_contents($data));
            return;
        }

        $fs->put($path, (string)$data);
    }

    private function isDirectory(string $path): bool
    {
        $fs = $this->filesystem->disk($this->disk);

        return in_array($path, $fs->directories(dirname($path)), true);
    }
}