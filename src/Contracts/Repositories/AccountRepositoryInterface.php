<?php

declare(strict_types=1);

namespace N3XT0R\LaravelWebdavServer\Contracts\Repositories;

use N3XT0R\LaravelWebdavServer\Contracts\Auth\AccountInterface;

interface AccountRepositoryInterface
{
    /**
     * Resolve the enabled WebDAV account for the given username.
     *
     * @param string $username Username supplied through Basic Auth.
     *
     * @throws \N3XT0R\LaravelWebdavServer\Exception\Auth\AccountNotFoundException When no account exists for the username.
     * @throws \N3XT0R\LaravelWebdavServer\Exception\Auth\AccountDisabledException When the account exists but is disabled.
     * @throws \N3XT0R\LaravelWebdavServer\Exception\Auth\InvalidAccountConfigurationException When the configured account model is invalid.
     * @throws \N3XT0R\LaravelWebdavServer\Exception\Auth\InvalidAccountRecordException When the resolved account record is missing required scalar values.
     *
     * @return \N3XT0R\LaravelWebdavServer\Contracts\Auth\AccountInterface Enabled account record for the username.
     */
    public function findEnabledByUsername(string $username): AccountInterface;
}
