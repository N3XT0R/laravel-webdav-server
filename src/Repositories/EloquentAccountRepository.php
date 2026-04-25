<?php

declare(strict_types=1);

namespace N3XT0R\LaravelWebdavServer\Repositories;

use Illuminate\Contracts\Config\Repository as Config;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;
use N3XT0R\LaravelWebdavServer\Contracts\Auth\AccountInterface;
use N3XT0R\LaravelWebdavServer\Contracts\Repositories\AccountManagementRepositoryInterface;
use N3XT0R\LaravelWebdavServer\Contracts\Repositories\AccountRepositoryInterface;
use N3XT0R\LaravelWebdavServer\DTO\Auth\AccountRecordDto;
use N3XT0R\LaravelWebdavServer\DTO\Management\AccountColumnMappingDto;
use N3XT0R\LaravelWebdavServer\Exception\Auth\AccountDisabledException;
use N3XT0R\LaravelWebdavServer\Exception\Auth\AccountNotFoundException;
use N3XT0R\LaravelWebdavServer\Exception\Auth\InvalidAccountConfigurationException;
use N3XT0R\LaravelWebdavServer\Exception\Auth\InvalidAccountRecordException;
use N3XT0R\LaravelWebdavServer\Models\WebDavAccountModel;

final class EloquentAccountRepository implements AccountManagementRepositoryInterface, AccountRepositoryInterface
{
    /**
     * @param  Config  $config  Package configuration repository used to resolve account-model settings.
     */
    public function __construct(
        private readonly Config $config,
    ) {}

    /**
     * Resolve the configured account model column mapping.
     *
     * @return AccountColumnMappingDto Resolved model class and column names for the configured account store.
     *
     * @throws InvalidAccountConfigurationException When `webdav-server.auth.account_model` is missing or invalid.
     */
    public function columnMapping(): AccountColumnMappingDto
    {
        $modelClass = $this->config->get('webdav-server.auth.account_model');

        if (! is_string($modelClass) || ! is_subclass_of($modelClass, Model::class)) {
            throw new InvalidAccountConfigurationException('Invalid or missing webdav-server.auth.account_model configuration.');
        }

        $usernameColumn = (string) $this->config->get('webdav-server.auth.username_column', 'username');

        return new AccountColumnMappingDto(
            modelClass: $modelClass,
            usernameColumn: $usernameColumn,
            passwordColumn: (string) $this->config->get('webdav-server.auth.password_column', 'password'),
            enabledColumn: $this->optionalColumn($this->config->get('webdav-server.auth.enabled_column', 'enabled')),
            userIdColumn: $this->optionalColumn($this->config->get('webdav-server.auth.user_id_column', 'user_id')),
            displayNameColumn: $this->optionalColumn(
                $this->config->get('webdav-server.auth.display_name_column', $usernameColumn),
            ),
        );
    }

    /**
     * Instantiate a fresh configured WebDAV account model without persisting it.
     *
     * @return Model New Eloquent model instance ready to be filled and saved.
     *
     * @throws InvalidAccountConfigurationException When the configured account model is invalid.
     */
    public function newModel(): Model
    {
        $modelClass = $this->columnMapping()->modelClass;

        return new $modelClass;
    }

    /**
     * Find one account by its username regardless of its enabled state.
     *
     * @param  string  $username  Username value to look up in the configured account model.
     * @return Model|null Matching account model, or `null` when no record exists for the given username.
     *
     * @throws InvalidAccountConfigurationException When the configured account model is invalid.
     */
    public function findByUsername(string $username): ?Model
    {
        $mapping = $this->columnMapping();

        return $mapping->modelClass::query()
            ->where($mapping->usernameColumn, $username)
            ->first();
    }

    /**
     * Return all accounts from the configured model ordered by their username column.
     *
     * @return Collection<int, Model> All persisted account records in ascending username order.
     *
     * @throws InvalidAccountConfigurationException When the configured account model is invalid.
     */
    public function all(): Collection
    {
        $mapping = $this->columnMapping();

        return $mapping->modelClass::query()
            ->orderBy($mapping->usernameColumn)
            ->get();
    }

    /**
     * Persist an account model instance.
     *
     * @param  Model  $account  Filled account model instance to persist.
     */
    public function save(Model $account): void
    {
        $account->save();
    }

    /**
     * Resolve the enabled WebDAV account for the supplied username from the configured Eloquent model.
     *
     * @param  string  $username  Username supplied through Basic Auth.
     * @return AccountInterface Enabled account record containing principal ID, display name, password hash, and optional linked user.
     *
     * @throws AccountNotFoundException When no account exists for the username.
     * @throws AccountDisabledException When the resolved account is disabled.
     * @throws InvalidAccountConfigurationException When the configured account model is invalid.
     * @throws InvalidAccountRecordException When the resolved record does not contain the required scalar fields.
     */
    public function findEnabledByUsername(string $username): AccountInterface
    {
        $mapping = $this->columnMapping();
        $principalIdColumn = (string) $this->config->get('webdav-server.auth.user_id_column', 'id');
        $displayNameColumn = (string) $this->config->get('webdav-server.auth.display_name_column', $mapping->usernameColumn);

        /** @var Model|WebDavAccountModel|null $account */
        $account = $mapping->modelClass::query()
            ->where($mapping->usernameColumn, $username)
            ->first();

        if ($account === null) {
            throw new AccountNotFoundException("No WebDAV account found for username '{$username}'.");
        }

        if ($mapping->enabledColumn !== null && ! (bool) $account->getAttribute($mapping->enabledColumn)) {
            throw new AccountDisabledException("WebDAV account '{$username}' is disabled.");
        }

        $principalId = $account->getAttribute($principalIdColumn);
        $displayName = $account->getAttribute($displayNameColumn);
        $passwordHash = $account->getAttribute($mapping->passwordColumn);

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

    /**
     * Normalize optional configured column names so that `null` represents a disabled mapping.
     *
     * @param  mixed  $value  Raw config value that may be `null`, an empty string, or a concrete column name.
     * @return string|null Normalized column name, or `null` when the mapping is intentionally disabled.
     */
    private function optionalColumn(mixed $value): ?string
    {
        if (! is_string($value)) {
            return null;
        }

        $column = trim($value);

        return $column === '' ? null : $column;
    }
}
