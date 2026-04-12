<?php

declare(strict_types=1);

namespace N3XT0R\LaravelWebdavServer\Nodes;

use Illuminate\Contracts\Filesystem\Factory as FilesystemManager;
use Sabre\DAV\Collection;
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

    public function getChildren(): array
    {
        $fs = $this->filesystem->disk($this->disk);

        $directories = $fs->directories($this->path);
        $files = $fs->files($this->path);

        $children = [];

        foreach ($directories as $dir) {
            $children[] = new self(
                basename($dir),
                $this->disk,
                $dir,
                $this->filesystem,
            );
        }

        foreach ($files as $file) {
            $children[] = new StorageFile(
                basename($file),
                $this->disk,
                $file,
                $this->filesystem,
            );
        }

        return $children;
    }

    public function getChild($name): INode
    {
        $path = $this->path.'/'.$name;
        $fs = $this->filesystem->disk($this->disk);

        if (!$fs->exists($path)) {
            throw new \Sabre\DAV\Exception\NotFound();
        }

        if (in_array($path, $fs->directories(dirname($path)), true)) {
            return new self($name, $this->disk, $path, $this->filesystem);
        }

        return new StorageFile($name, $this->disk, $path, $this->filesystem);
    }
}