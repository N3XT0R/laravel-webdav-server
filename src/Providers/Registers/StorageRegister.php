<?php

declare(strict_types=1);

namespace N3XT0R\LaravelWebdavServer\Providers\Registers;

use N3XT0R\LaravelWebdavServer\Contracts\Server\StorageRootBuilderInterface;
use N3XT0R\LaravelWebdavServer\Contracts\Storage\PathResolverInterface;
use N3XT0R\LaravelWebdavServer\Contracts\Storage\SpaceResolverInterface;
use N3XT0R\LaravelWebdavServer\Server\Storage\StorageRootBuilder;
use N3XT0R\LaravelWebdavServer\Services\PathResolverService;
use N3XT0R\LaravelWebdavServer\Storage\Resolvers\DefaultSpaceResolver;

final readonly class StorageRegister extends AbstractRegister
{
    protected function bindings(): array
    {
        return [
            PathResolverInterface::class => PathResolverService::class,
            SpaceResolverInterface::class => DefaultSpaceResolver::class,
            StorageRootBuilderInterface::class => StorageRootBuilder::class,
        ];
    }
}
