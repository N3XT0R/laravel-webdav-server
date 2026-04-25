<?php

declare(strict_types=1);

namespace N3XT0R\LaravelWebdavServer\DTO\Auth;

use Illuminate\Contracts\Auth\Authenticatable;
use N3XT0R\LaravelWebdavServer\Contracts\Auth\AccountInterface;

final readonly class AccountRecordDto implements AccountInterface
{
    /**
     * Create an immutable account record used by the default repository and validator pipeline.
     *
     * @param string $principalId Principal identifier that will back the WebDAV principal URI.
     * @param string $displayName Human-readable display name for the account.
     * @param string $passwordHash Stored password hash used for credential verification.
     * @param \Illuminate\Contracts\Auth\Authenticatable|null $user Linked Laravel user or null when the account is standalone.
     */
    public function __construct(
        protected string $principalId,
        protected string $displayName,
        protected string $passwordHash,
        protected ?Authenticatable $user = null,
    ) {}

    /**
     * Return the WebDAV principal identifier for the account.
     *
     * @return string Principal identifier used in authenticated WebDAV contexts.
     */
    public function getPrincipalId(): string
    {
        return $this->principalId;
    }

    /**
     * Return the display name for the account.
     *
     * @return string Human-readable account name.
     */
    public function getDisplayName(): string
    {
        return $this->displayName;
    }

    /**
     * Return the stored password hash for the account.
     *
     * @return string Password hash used by the default credential validator.
     */
    public function getPasswordHash(): string
    {
        return $this->passwordHash;
    }

    /**
     * Return the linked Laravel user for Gate / policy integration.
     *
     * @return \Illuminate\Contracts\Auth\Authenticatable|null Linked Laravel user or null when no user is associated.
     */
    public function getUser(): ?Authenticatable
    {
        return $this->user;
    }
}
