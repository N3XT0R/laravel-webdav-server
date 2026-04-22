<?php

declare(strict_types=1);

namespace N3XT0R\LaravelWebdavServer\Providers\Registers;

use N3XT0R\LaravelWebdavServer\Contracts\Repositories\WebDavAccountRepositoryInterface;
use N3XT0R\LaravelWebdavServer\Repositories\EloquentWebDavAccountRepository;

final class RepositoryRegister extends AbstractRegister
{
    protected function bindings(): array
    {
        return [
            WebDavAccountRepositoryInterface::class => EloquentWebDavAccountRepository::class,
        ];
    }
}
