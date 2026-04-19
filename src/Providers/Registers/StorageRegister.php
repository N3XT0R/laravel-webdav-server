<?php

declare(strict_types=1);

namespace N3XT0R\LaravelWebdavServer\Providers\Registers;

use Illuminate\Container\Container;
use N3XT0R\LaravelWebdavServer\Contracts\Server\StorageRootBuilderInterface;
use N3XT0R\LaravelWebdavServer\Contracts\Storage\SpaceResolverInterface;
use N3XT0R\LaravelWebdavServer\Server\Storage\StorageRootBuilder;
use N3XT0R\LaravelWebdavServer\Storage\Resolvers\DefaultSpaceResolver;

final readonly class StorageRegister
{
    public function __construct(
        private Container $app,
    ) {}

    public function register(): void
    {
        $this->app->bindIf(
            SpaceResolverInterface::class,
            DefaultSpaceResolver::class,
        );

        $this->app->bindIf(
            StorageRootBuilderInterface::class,
            StorageRootBuilder::class,
        );
    }
}
