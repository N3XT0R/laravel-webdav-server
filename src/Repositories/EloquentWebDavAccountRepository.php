<?php

declare(strict_types=1);

namespace N3XT0R\LaravelWebdavServer\Repositories;

use Illuminate\Contracts\Config\Repository as Config;
use Illuminate\Database\Eloquent\Model;
use N3XT0R\LaravelWebdavServer\Contracts\Auth\WebDavAccountInterface;
use N3XT0R\LaravelWebdavServer\Contracts\Repositories\WebDavAccountRepositoryInterface;
use N3XT0R\LaravelWebdavServer\DTO\Auth\WebDavAccountRecordDto;
use N3XT0R\LaravelWebdavServer\Models\WebDavAccountModel;
use RuntimeException;

final readonly class EloquentWebDavAccountRepository implements WebDavAccountRepositoryInterface
{
    public function __construct(
        private Config $config,
    ) {}

    public function findEnabledByUsername(string $username): ?WebDavAccountInterface
    {
        $modelClass = $this->config->get('webdav-server.auth.account_model');

        if (! is_string($modelClass) || ! is_subclass_of($modelClass, Model::class)) {
            throw new RuntimeException('Invalid or missing webdav-server.auth.account_model configuration');
        }

        $usernameColumn = (string) $this->config->get('webdav-server.auth.username_column', 'username');
        $passwordColumn = (string) $this->config->get('webdav-server.auth.password_column', 'password');
        $enabledColumn = $this->config->get('webdav-server.auth.enabled_column', 'enabled');
        $principalIdColumn = (string) $this->config->get('webdav-server.auth.user_id_column', 'id');
        $displayNameColumn = (string) $this->config->get('webdav-server.auth.display_name_column', $usernameColumn);

        /** @var Model|WebDavAccountModel|null $account */
        $account = $modelClass::query()
            ->where($usernameColumn, $username)
            ->when(
                is_string($enabledColumn) && $enabledColumn !== '',
                fn ($query) => $query->where($enabledColumn, true)
            )
            ->first();

        if (! $account) {
            return null;
        }

        $principalId = $account->getAttribute($principalIdColumn);
        $displayName = $account->getAttribute($displayNameColumn);
        $passwordHash = $account->getAttribute($passwordColumn);

        if (! is_scalar($principalId) || ! is_scalar($displayName) || ! is_scalar($passwordHash)) {
            throw new RuntimeException('WebDAV auth model returned invalid scalar attributes');
        }

        return new WebDavAccountRecordDto(
            (string) $principalId,
            (string) $displayName,
            (string) $passwordHash,
            $account->user
        );
    }
}
