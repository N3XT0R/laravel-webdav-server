<?php

declare(strict_types=1);

namespace N3XT0R\LaravelWebdavServer\Nodes;

use N3XT0R\LaravelWebdavServer\DTO\Storage\StorageNodeContextDto;
use N3XT0R\LaravelWebdavServer\Events\WebDav\FileDeletedEvent;
use N3XT0R\LaravelWebdavServer\Events\WebDav\FileUpdatedEvent;
use N3XT0R\LaravelWebdavServer\Exception\Storage\StreamReadException;
use Sabre\DAV\Exception\Forbidden;
use Sabre\DAV\File;

final class StorageFile extends File
{
    /**
     * @param  string  $name  Node name exposed to SabreDAV for this file.
     * @param  string  $path  Relative storage path represented by this file.
     * @param  StorageNodeContextDto  $context  Shared storage context with filesystem, principal, disk, and authorization service.
     */
    public function __construct(
        private readonly string $name,
        private readonly string $path,
        private readonly StorageNodeContextDto $context,
    ) {}

    /**
     * Returns the current file name as exposed in the WebDAV tree.
     *
     * @return string File name visible to SabreDAV clients.
     */
    public function getName(): string
    {
        return $this->name;
    }

    /**
     * Reads the current file contents after a read authorization check.
     *
     * @return string Full file contents as a UTF-8 or binary-safe PHP string.
     *
     * @throws Forbidden When the current principal may not read the file.
     */
    public function get(): string
    {
        $this->context->authorization->authorizeRead(
            $this->context->principal,
            $this->context->disk,
            $this->path,
        );

        return $this->context->filesystem
            ->get($this->path);
    }

    /**
     * Overwrites the current file with the provided contents.
     *
     * @param  resource|string  $data  New file contents as a stream or plain string.
     *
     * @throws StreamReadException When the provided stream cannot be read.
     * @throws Forbidden When the current principal may not update the file.
     */
    public function put($data): void
    {
        $this->context->authorization->authorizeWrite(
            $this->context->principal,
            $this->context->disk,
            $this->path,
        );

        $fs = $this->context->filesystem;

        if (is_resource($data)) {
            $contents = stream_get_contents($data);

            if ($contents === false) {
                throw new StreamReadException('Failed to read file stream.');
            }

            $fs->put($this->path, $contents);
            $this->dispatchUpdated($contents);

            return;
        }

        $contents = (string) $data;
        $fs->put($this->path, $contents);
        $this->dispatchUpdated($contents);
    }

    /**
     * Deletes the current file after a delete authorization check.
     *
     * @throws Forbidden When the current principal may not delete the file.
     */
    public function delete(): void
    {
        $this->context->authorization->authorizeDelete(
            $this->context->principal,
            $this->context->disk,
            $this->path,
        );

        $this->context->filesystem
            ->delete($this->path);

        FileDeletedEvent::dispatch(
            disk: $this->context->disk,
            path: $this->path,
            principal: $this->context->principal,
        );
    }

    /**
     * Returns the current file size in bytes.
     *
     * @return int File size in bytes.
     *
     * @throws Forbidden When the current principal may not read the file metadata.
     */
    public function getSize(): int
    {
        $this->context->authorization->authorizeRead(
            $this->context->principal,
            $this->context->disk,
            $this->path,
        );

        return $this->context->filesystem
            ->size($this->path);
    }

    /**
     * Returns the UNIX timestamp of the last file modification.
     *
     * @return int Last-modified timestamp in seconds.
     *
     * @throws Forbidden When the current principal may not read the file metadata.
     */
    public function getLastModified(): int
    {
        $this->context->authorization->authorizeRead(
            $this->context->principal,
            $this->context->disk,
            $this->path,
        );

        return $this->context->filesystem
            ->lastModified($this->path);
    }

    private function dispatchUpdated(string $contents): void
    {
        FileUpdatedEvent::dispatch(
            disk: $this->context->disk,
            path: $this->path,
            principal: $this->context->principal,
            bytes: strlen($contents),
        );
    }
}
