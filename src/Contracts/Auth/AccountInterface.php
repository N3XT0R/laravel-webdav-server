<?php

declare(strict_types=1);

namespace N3XT0R\LaravelWebdavServer\Contracts\Auth;

use Illuminate\Contracts\Auth\Authenticatable;

interface AccountInterface
{
    public function getPrincipalId(): string;

    public function getDisplayName(): string;

    public function getPasswordHash(): string;

    public function getUser(): ?Authenticatable;
}
