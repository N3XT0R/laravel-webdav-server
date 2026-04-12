<?php

declare(strict_types=1);

namespace N3XT0R\LaravelWebdavServer\Contracts\Repositories;

use N3XT0R\LaravelWebdavServer\Contracts\Auth\WebDavAccountInterface;

interface WebDavAccountRepositoryInterface
{
    public function findEnabledByUsername(string $username): ?WebDavAccountInterface;
}
