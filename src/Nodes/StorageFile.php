<?php

declare(strict_types=1);

namespace N3XT0R\LaravelWebdavServer\Nodes;

use N3XT0R\LaravelWebdavServer\DTO\Storage\StorageNodeContextDto;
use Sabre\DAV\File;

final class StorageFile extends File
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

    public function get(): string
    {
        $this->context->authorization->authorizeRead(
            $this->context->principal,
            $this->disk,
            $this->path,
        );

        return $this->context->filesystem
            ->disk($this->disk)
            ->get($this->path);
    }

    public function put($data): void
    {
        $this->context->authorization->authorizeWrite(
            $this->context->principal,
            $this->disk,
            $this->path,
        );

        $fs = $this->context->filesystem->disk($this->disk);

        if (is_resource($data)) {
            $contents = stream_get_contents($data);

            if ($contents === false) {
                throw new \RuntimeException('Failed to read file stream.');
            }

            $fs->put($this->path, $contents);

            return;
        }

        $fs->put($this->path, (string)$data);
    }

    public function delete(): void
    {
        $this->context->authorization->authorizeDelete(
            $this->context->principal,
            $this->disk,
            $this->path,
        );

        $this->context->filesystem
            ->disk($this->disk)
            ->delete($this->path);
    }

    public function getSize(): int
    {
        $this->context->authorization->authorizeRead(
            $this->context->principal,
            $this->disk,
            $this->path,
        );

        return $this->context->filesystem
            ->disk($this->disk)
            ->size($this->path);
    }

    public function getLastModified(): int
    {
        $this->context->authorization->authorizeRead(
            $this->context->principal,
            $this->disk,
            $this->path,
        );

        return $this->context->filesystem
            ->disk($this->disk)
            ->lastModified($this->path);
    }
}
