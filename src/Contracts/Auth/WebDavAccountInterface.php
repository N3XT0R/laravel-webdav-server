<?php

declare(strict_types=1);

namespace N3XT0R\LaravelWebdavServer\Contracts\Auth;

interface WebDavAccountInterface
{
    public function getPrincipalId(): string;

    public function getDisplayName(): string;

    public function getPasswordHash(): string;
}