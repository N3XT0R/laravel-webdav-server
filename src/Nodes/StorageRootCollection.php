<?php

declare(strict_types=1);

namespace N3XT0R\LaravelWebdavServer\Nodes;

use N3XT0R\LaravelWebdavServer\DTO\Storage\StorageNodeContextDto;

final class StorageRootCollection extends AbstractStorageCollection
{
    /**
     * @param string $name Root collection name exposed to SabreDAV.
     * @param string $rootPath Relative storage root path that backs the WebDAV tree.
     * @param StorageNodeContextDto $context Shared storage context with filesystem, principal, disk, and authorization service.
     */
    public function __construct(string $name, string $rootPath, StorageNodeContextDto $context)
    {
        parent::__construct($name, $rootPath, $context);
    }
}
