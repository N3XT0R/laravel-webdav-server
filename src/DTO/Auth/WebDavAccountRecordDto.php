<?php

declare(strict_types=1);

namespace N3XT0R\LaravelWebdavServer\DTO\Auth;

use N3XT0R\LaravelWebdavServer\Contracts\Auth\WebDavAccountInterface;

readonly class WebDavAccountRecordDto implements WebDavAccountInterface
{
    public function __construct(
        protected string $principalId,
        protected string $displayName,
        protected string $passwordHash,
    ) {
    }

    public function getPrincipalId(): string
    {
        return $this->principalId;
    }

    public function getDisplayName(): string
    {
        return $this->displayName;
    }

    public function getPasswordHash(): string
    {
        return $this->passwordHash;
    }
}