<?php

declare(strict_types=1);

namespace N3XT0R\LaravelWebdavServer\Repositories;

use Illuminate\Contracts\Config\Repository as Config;
use Illuminate\Database\Eloquent\Model;
use N3XT0R\LaravelWebdavServer\Contracts\Auth\AccountInterface;
use N3XT0R\LaravelWebdavServer\Contracts\Repositories\AccountRepositoryInterface;
use N3XT0R\LaravelWebdavServer\DTO\Auth\AccountRecordDto;
use N3XT0R\LaravelWebdavServer\Exception\Auth\AccountDisabledException;
use N3XT0R\LaravelWebdavServer\Exception\Auth\AccountNotFoundException;
use N3XT0R\LaravelWebdavServer\Exception\Auth\InvalidAccountConfigurationException;
use N3XT0R\LaravelWebdavServer\Exception\Auth\InvalidAccountRecordException;
use N3XT0R\LaravelWebdavServer\Models\WebDavAccountModel;

final readonly class EloquentAccountRepository implements AccountRepositoryInterface
{
    public function __construct(
        private Config $config,
    ) {}

    public function findEnabledByUsername(string $username): AccountInterface
    {
        $modelClass = $this->config->get('webdav-server.auth.account_model');

        if (! is_string($modelClass) || ! is_subclass_of($modelClass, Model::class)) {
            throw new InvalidAccountConfigurationException('Invalid or missing webdav-server.auth.account_model configuration.');
        }

        $usernameColumn = (string) $this->config->get('webdav-server.auth.username_column', 'username');
        $passwordColumn = (string) $this->config->get('webdav-server.auth.password_column', 'password');
        $enabledColumn = $this->config->get('webdav-server.auth.enabled_column', 'enabled');
        $principalIdColumn = (string) $this->config->get('webdav-server.auth.user_id_column', 'id');
        $displayNameColumn = (string) $this->config->get('webdav-server.auth.display_name_column', $usernameColumn);

        /** @var Model|WebDavAccountModel|null $account */
        $account = $modelClass::query()
            ->where($usernameColumn, $username)
            ->first();

        if ($account === null) {
            throw new AccountNotFoundException("No WebDAV account found for username '{$username}'.");
        }

        if (is_string($enabledColumn) && $enabledColumn !== '' && ! (bool) $account->getAttribute($enabledColumn)) {
            throw new AccountDisabledException("WebDAV account '{$username}' is disabled.");
        }

        $principalId = $account->getAttribute($principalIdColumn);
        $displayName = $account->getAttribute($displayNameColumn);
        $passwordHash = $account->getAttribute($passwordColumn);

        if (! is_scalar($principalId) || ! is_scalar($displayName) || ! is_scalar($passwordHash)) {
            throw new InvalidAccountRecordException('WebDAV auth model returned invalid scalar attributes.');
        }

        return new AccountRecordDto(
            (string) $principalId,
            (string) $displayName,
            (string) $passwordHash,
            $account->user,
        );
    }
}
