<?php

declare(strict_types=1);

namespace N3XT0R\LaravelWebdavServer\Nodes;

use N3XT0R\LaravelWebdavServer\DTO\Storage\StorageNodeContextDto;
use N3XT0R\LaravelWebdavServer\Events\WebDavDirectoryCreatedEvent;
use N3XT0R\LaravelWebdavServer\Events\WebDavFileCreatedEvent;
use N3XT0R\LaravelWebdavServer\Exception\Storage\StreamReadException;
use Sabre\DAV\Collection;
use Sabre\DAV\Exception\Forbidden;
use Sabre\DAV\Exception\NotFound;
use Sabre\DAV\INode;

abstract class AbstractStorageCollection extends Collection
{
    /**
     * @param  string  $name  Node name exposed to SabreDAV for this collection.
     * @param  string  $path  Relative storage path represented by this collection.
     * @param  StorageNodeContextDto  $context  Shared storage context with filesystem, principal, disk, and authorization service.
     */
    public function __construct(
        protected readonly string $name,
        protected readonly string $path,
        protected readonly StorageNodeContextDto $context,
    ) {}

    /**
     * Returns the current collection name as exposed in the WebDAV tree.
     *
     * @return string Collection name visible to SabreDAV clients.
     */
    public function getName(): string
    {
        return $this->name;
    }

    /**
     * Lists the direct child nodes below the current collection after a read authorization check.
     *
     * @return list<INode>
     *                     List of immediate child nodes. Directory entries are returned as `StorageDirectory`, file entries as `StorageFile`.
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

    /**
     * Resolves one direct child node by name.
     *
     * @param  mixed  $name  Child node name received from SabreDAV.
     * @return INode Resolved child node as either `StorageDirectory` or `StorageFile`.
     *
     * @throws NotFound When the requested child path does not exist.
     * @throws Forbidden When the current principal may not read the requested child path.
     */
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

    /**
     * Checks whether a direct child exists and is readable for the current principal.
     *
     * @param  mixed  $name  Child node name received from SabreDAV.
     * @return bool `true` when the child exists and the principal may read it; otherwise `false`.
     */
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

    /**
     * Creates a new child directory below the current collection.
     *
     * @param  mixed  $name  Directory name received from SabreDAV.
     *
     * @throws Forbidden When the current principal may not create the directory.
     */
    public function createDirectory($name): void
    {
        $path = $this->buildChildPath((string) $name);

        $this->context->authorization->authorizeCreateDirectory(
            $this->context->principal,
            $this->context->disk,
            $path,
        );

        $this->context->filesystem->makeDirectory($path);
        WebDavDirectoryCreatedEvent::dispatch(
            disk: $this->context->disk,
            path: $path,
            principal: $this->context->principal,
        );
    }

    /**
     * Creates a new child file below the current collection and stores the provided contents.
     *
     * @param  mixed  $name  File name received from SabreDAV.
     * @param  resource|string|null  $data  File contents as a stream, plain string, or `null` for an empty file.
     *
     * @throws StreamReadException When the provided stream cannot be read.
     * @throws Forbidden When the current principal may not create the file.
     */
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
                throw new StreamReadException('Failed to read file stream.');
            }

            $fs->put($path, $contents);
            $this->dispatchFileCreated($path, $contents);

            return;
        }

        $contents = (string) ($data ?? '');
        $fs->put($path, $contents);
        $this->dispatchFileCreated($path, $contents);
    }

    protected function dispatchFileCreated(string $path, string $contents): void
    {
        WebDavFileCreatedEvent::dispatch(
            disk: $this->context->disk,
            path: $path,
            principal: $this->context->principal,
            bytes: strlen($contents),
        );
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
