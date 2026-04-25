<?php

declare(strict_types=1);

namespace N3XT0R\LaravelWebdavServer\Providers\Registers;

use N3XT0R\LaravelWebdavServer\Contracts\Repositories\AccountManagementRepositoryInterface;
use N3XT0R\LaravelWebdavServer\Contracts\Repositories\AccountRepositoryInterface;
use N3XT0R\LaravelWebdavServer\Repositories\EloquentAccountRepository;

final readonly class RepositoryRegister extends AbstractRegister
{
    protected function bindings(): array
    {
        return [
            AccountRepositoryInterface::class => EloquentAccountRepository::class,
            AccountManagementRepositoryInterface::class => EloquentAccountRepository::class,
        ];
    }
}
