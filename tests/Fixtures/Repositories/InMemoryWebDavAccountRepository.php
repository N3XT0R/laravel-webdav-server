<?php

declare(strict_types=1);

namespace N3XT0R\LaravelWebdavServer\Tests\Fixtures\Repositories;

use N3XT0R\LaravelWebdavServer\Contracts\Auth\WebDavAccountInterface;
use N3XT0R\LaravelWebdavServer\Contracts\Repositories\WebDavAccountRepositoryInterface;

final class InMemoryWebDavAccountRepository implements WebDavAccountRepositoryInterface
{
    /** @var list<string> */
    public array $lookups = [];

    /**
     * @param array<string, WebDavAccountInterface> $accounts
     */
    public function __construct(
        private array $accounts = [],
    ) {}

    public function findEnabledByUsername(string $username): ?WebDavAccountInterface
    {
        $this->lookups[] = $username;

        return $this->accounts[$username] ?? null;
    }
}
