<?php

declare(strict_types=1);

namespace N3XT0R\LaravelWebdavServer\Contracts\Repositories;

use N3XT0R\LaravelWebdavServer\Contracts\Auth\AccountInterface;

interface AccountRepositoryInterface
{
    public function findEnabledByUsername(string $username): AccountInterface;
}
