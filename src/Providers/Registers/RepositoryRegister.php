<?php

declare(strict_types=1);

namespace N3XT0R\LaravelWebdavServer\Providers\Registers;

use Illuminate\Container\Container;
use N3XT0R\LaravelWebdavServer\Contracts\Repositories\WebDavAccountRepositoryInterface;
use N3XT0R\LaravelWebdavServer\Repositories\EloquentWebDavAccountRepository;

final readonly class RepositoryRegister
{
    public function __construct(
        private Container $app,
    ) {}

    public function register(): void
    {
        $this->app->bindIf(
            WebDavAccountRepositoryInterface::class,
            EloquentWebDavAccountRepository::class,
        );
    }
}
