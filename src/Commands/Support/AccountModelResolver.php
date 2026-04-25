<?php

declare(strict_types=1);

namespace N3XT0R\LaravelWebdavServer\Commands\Support;

use Illuminate\Contracts\Config\Repository;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use N3XT0R\LaravelWebdavServer\Exception\Auth\InvalidAccountConfigurationException;

final readonly class AccountModelResolver
{
    /**
     * Create the command helper that resolves the configured WebDAV account model and column mapping.
     *
     * @param  Repository  $config  Package configuration repository used to resolve `webdav-server.auth.*`.
     */
    public function __construct(
        private Repository $config,
    ) {}

    /**
     * Resolve the configured account model and relevant auth columns for the artisan account commands.
     *
     * @return AccountModelConfiguration Normalized model class and column mapping for command-side account management.
     *
     * @throws InvalidAccountConfigurationException When `webdav-server.auth.account_model` is missing or not an Eloquent model.
     */
    public function configuration(): AccountModelConfiguration
    {
        $modelClass = $this->config->get('webdav-server.auth.account_model');

        if (! is_string($modelClass) || ! is_subclass_of($modelClass, Model::class)) {
            throw new InvalidAccountConfigurationException('Invalid or missing webdav-server.auth.account_model configuration.');
        }

        return new AccountModelConfiguration(
            $modelClass,
            (string) $this->config->get('webdav-server.auth.username_column', 'username'),
            (string) $this->config->get('webdav-server.auth.password_column', 'password'),
            $this->optionalColumn($this->config->get('webdav-server.auth.enabled_column', 'enabled')),
            $this->optionalColumn($this->config->get('webdav-server.auth.user_id_column', 'user_id')),
            $this->optionalColumn(
                $this->config->get(
                    'webdav-server.auth.display_name_column',
                    $this->config->get('webdav-server.auth.username_column', 'username'),
                ),
            ),
        );
    }

    /**
     * Start a fresh Eloquent query for the configured WebDAV account model.
     *
     * @return Builder<Model> Query builder for reading or mutating configured WebDAV account records.
     *
     * @throws InvalidAccountConfigurationException When the configured account model is invalid.
     */
    public function query(): Builder
    {
        $configuration = $this->configuration();
        $modelClass = $configuration->modelClass;

        return $modelClass::query();
    }

    /**
     * Instantiate a fresh configured WebDAV account model.
     *
     * @return Model New Eloquent model instance for persisting a WebDAV account record.
     *
     * @throws InvalidAccountConfigurationException When the configured account model is invalid.
     */
    public function newModel(): Model
    {
        $configuration = $this->configuration();
        $modelClass = $configuration->modelClass;

        return new $modelClass;
    }

    /**
     * Resolve one configured WebDAV account model by its username column.
     *
     * @param  string  $username  Username value to look up in the configured account model.
     * @return Model|null Matching account model instance, or `null` when no record exists for the username.
     *
     * @throws InvalidAccountConfigurationException When the configured account model is invalid.
     */
    public function findByUsername(string $username): ?Model
    {
        $configuration = $this->configuration();

        return $this->query()
            ->where($configuration->usernameColumn, $username)
            ->first();
    }

    /**
     * Normalize optional configured column names so commands can distinguish enabled columns from disabled mappings.
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
