<?php

declare(strict_types=1);

namespace N3XT0R\LaravelWebdavServer\Nodes;

use Sabre\DAV\Exception\Forbidden;

final class StorageDirectory extends AbstractStorageCollection
{
    /**
     * Deletes the directory and all nested files and directories after per-node authorization checks.
     *
     * @throws Forbidden When the current principal may not delete the directory or one of its children.
     */
    public function delete(): void
    {
        $this->context->authorization->authorizeDelete(
            $this->context->principal,
            $this->context->disk,
            $this->path,
        );

        $this->deleteRecursively($this->context->filesystem, $this->path);
    }

    private function deleteRecursively(object $fs, string $path): void
    {
        foreach ($fs->files($path) as $file) {
            $this->context->authorization->authorizeDelete(
                $this->context->principal,
                $this->context->disk,
                $file,
            );

            $fs->delete($file);
        }

        foreach ($fs->directories($path) as $directory) {
            $this->context->authorization->authorizeDelete(
                $this->context->principal,
                $this->context->disk,
                $directory,
            );

            $this->deleteRecursively($fs, $directory);
        }

        $fs->deleteDirectory($path);
    }
}
