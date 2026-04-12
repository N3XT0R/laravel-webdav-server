<?php

declare(strict_types=1);

namespace N3XT0R\LaravelWebdavServer\Nodes;

use Illuminate\Contracts\Filesystem\Factory as FilesystemManager;
use Sabre\DAV\File;

final class StorageFile extends File
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

    public function get(): string
    {
        return $this->filesystem->disk($this->disk)->get($this->path);
    }

    public function put($data): void
    {
        if (is_resource($data)) {
            $data = stream_get_contents($data);
        }

        $this->filesystem->disk($this->disk)->put($this->path, (string)$data);
    }

    public function delete(): void
    {
        $this->filesystem->disk($this->disk)->delete($this->path);
    }

    public function getSize(): int
    {
        return $this->filesystem->disk($this->disk)->size($this->path);
    }

    public function getLastModified(): int
    {
        return $this->filesystem->disk($this->disk)->lastModified($this->path);
    }
}