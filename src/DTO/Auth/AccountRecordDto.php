<?php

declare(strict_types=1);

namespace N3XT0R\LaravelWebdavServer\DTO\Auth;

use Illuminate\Contracts\Auth\Authenticatable;
use N3XT0R\LaravelWebdavServer\Contracts\Auth\AccountInterface;

final readonly class AccountRecordDto implements AccountInterface
{
    public function __construct(
        protected string $principalId,
        protected string $displayName,
        protected string $passwordHash,
        protected ?Authenticatable $user = null,
    ) {}

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

    public function getUser(): ?Authenticatable
    {
        return $this->user;
    }
}
