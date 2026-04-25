<?php

declare(strict_types=1);

namespace N3XT0R\LaravelWebdavServer\Tests\Fixtures\Repositories;

use N3XT0R\LaravelWebdavServer\Contracts\Auth\AccountInterface;
use N3XT0R\LaravelWebdavServer\Contracts\Repositories\AccountRepositoryInterface;
use N3XT0R\LaravelWebdavServer\Exception\Auth\AccountNotFoundException;

final class InMemoryAccountRepository implements AccountRepositoryInterface
{
    /** @var list<string> */
    public array $lookups = [];

    /**
     * @param  array<string, AccountInterface>  $accounts
     */
    public function __construct(
        private array $accounts = [],
    ) {}

    public function findEnabledByUsername(string $username): AccountInterface
    {
        $this->lookups[] = $username;

        if (isset($this->accounts[$username])) {
            return $this->accounts[$username];
        }

        throw new AccountNotFoundException("No WebDAV account found for username '{$username}'.");
    }
}
