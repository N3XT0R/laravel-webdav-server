<?php

declare(strict_types=1);

namespace N3XT0R\LaravelWebdavServer\Contracts\Repositories;

use N3XT0R\LaravelWebdavServer\Contracts\Auth\AccountInterface;
use N3XT0R\LaravelWebdavServer\Exception\Auth\AccountDisabledException;
use N3XT0R\LaravelWebdavServer\Exception\Auth\AccountNotFoundException;
use N3XT0R\LaravelWebdavServer\Exception\Auth\InvalidAccountConfigurationException;
use N3XT0R\LaravelWebdavServer\Exception\Auth\InvalidAccountRecordException;

interface AccountRepositoryInterface
{
    /**
     * Resolve the enabled WebDAV account for the given username.
     *
     * @param  string  $username  Username supplied through Basic Auth.
     * @return AccountInterface Enabled account record for the username.
     *
     * @throws AccountNotFoundException When no account exists for the username.
     * @throws AccountDisabledException When the account exists but is disabled.
     * @throws InvalidAccountConfigurationException When the configured account model is invalid.
     * @throws InvalidAccountRecordException When the resolved account record is missing required scalar values.
     */
    public function findEnabledByUsername(string $username): AccountInterface;
}
