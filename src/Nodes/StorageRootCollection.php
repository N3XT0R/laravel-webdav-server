<?php

declare(strict_types=1);

namespace N3XT0R\LaravelWebdavServer\Nodes;

use N3XT0R\LaravelWebdavServer\DTO\Storage\StorageNodeContextDto;

final class StorageRootCollection extends AbstractStorageCollection
{
    public function __construct(string $name, string $rootPath, StorageNodeContextDto $context)
    {
        parent::__construct($name, $rootPath, $context);
    }
}
