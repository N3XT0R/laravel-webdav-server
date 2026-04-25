<?php

declare(strict_types=1);

namespace N3XT0R\LaravelWebdavServer\Commands;

use Illuminate\Console\Command;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Hash;
use N3XT0R\LaravelWebdavServer\Commands\Support\AccountModelConfiguration;
use N3XT0R\LaravelWebdavServer\Commands\Support\AccountModelResolver;

final class CreateWebDavAccountCommand extends Command
{
    protected $signature = 'laravel-webdav-server:account:create
        {username : Username used for HTTP Basic Auth.}
        {password : Plain-text password that will be hashed before storage.}
        {--display-name= : Optional principal display name shown to WebDAV clients.}
        {--user-id= : Optional linked Laravel user identifier.}
        {--disabled : Create the account in a disabled state.}';

    protected $description = 'Create a WebDAV account record in the configured account model.';

    /**
     * Create a new WebDAV account in the configured account model.
     *
     * @param  AccountModelResolver  $accountResolver  Helper that resolves the configured account model and column mapping.
     * @return int Symfony-compatible command exit code.
     */
    public function handle(AccountModelResolver $accountResolver): int
    {
        $configuration = $accountResolver->configuration();
        $username = (string) $this->argument('username');

        if ($accountResolver->findByUsername($username) !== null) {
            $this->components->error("A WebDAV account with username '{$username}' already exists.");

            return self::FAILURE;
        }

        $account = $accountResolver->newModel();
        $this->fillRequiredAttributes($account, $configuration, $username, (string) $this->argument('password'));
        $this->fillOptionalAttributes($account, $configuration);
        $account->save();

        $this->components->info("Created WebDAV account '{$username}'.");
        $this->renderAccountSummary($account, $configuration);

        return self::SUCCESS;
    }

    /**
     * Populate the always-required username and password fields on the new account model.
     *
     * @param  Model  $account  Configured Eloquent account model instance being created.
     * @param  AccountModelConfiguration  $configuration  Resolved account column mapping from package config.
     * @param  string  $username  Username that clients will use for Basic Auth.
     * @param  string  $password  Plain-text password that will be hashed before persistence.
     */
    private function fillRequiredAttributes(
        Model $account,
        AccountModelConfiguration $configuration,
        string $username,
        string $password,
    ): void {
        $account->setAttribute($configuration->usernameColumn, $username);
        $account->setAttribute($configuration->passwordColumn, Hash::make($password));

        if ($configuration->displayNameColumn !== null && $configuration->displayNameColumn !== $configuration->usernameColumn) {
            $account->setAttribute(
                $configuration->displayNameColumn,
                $this->displayNameValue($username),
            );
        }
    }

    /**
     * Populate optional enabled, linked-user, and display-name attributes on the new account model.
     *
     * @param  Model  $account  Configured Eloquent account model instance being created.
     * @param  AccountModelConfiguration  $configuration  Resolved account column mapping from package config.
     */
    private function fillOptionalAttributes(Model $account, AccountModelConfiguration $configuration): void
    {
        if ($configuration->enabledColumn !== null) {
            $account->setAttribute($configuration->enabledColumn, ! (bool) $this->option('disabled'));
        }

        if ($configuration->userIdColumn !== null && $this->option('user-id') !== null) {
            $account->setAttribute($configuration->userIdColumn, $this->option('user-id'));
        }

        if ($configuration->displayNameColumn !== null) {
            $account->setAttribute($configuration->displayNameColumn, $this->displayNameValue(
                (string) $account->getAttribute($configuration->usernameColumn),
            ));
        }
    }

    /**
     * Resolve the display name that should be stored for the new account.
     *
     * @param  string  $username  Username used as the fallback display name when no explicit name was provided.
     * @return string Display name that should be persisted for the created account.
     */
    private function displayNameValue(string $username): string
    {
        $displayName = $this->option('display-name');

        return is_string($displayName) && trim($displayName) !== ''
            ? $displayName
            : $username;
    }

    /**
     * Render a concise summary of the created account for console users.
     *
     * @param  Model  $account  Newly persisted Eloquent account model instance.
     * @param  AccountModelConfiguration  $configuration  Resolved account column mapping from package config.
     */
    private function renderAccountSummary(Model $account, AccountModelConfiguration $configuration): void
    {
        $this->table(
            ['Field', 'Value'],
            [
                ['username', (string) $account->getAttribute($configuration->usernameColumn)],
                ['enabled', $this->booleanValue($account, $configuration->enabledColumn)],
                ['user_id', $this->stringValue($account, $configuration->userIdColumn)],
                ['display_name', $this->stringValue($account, $configuration->displayNameColumn)],
            ],
        );
    }

    /**
     * Convert a configured optional account column into a printable string value.
     *
     * @param  Model  $account  Account model whose attribute should be rendered.
     * @param  string|null  $column  Optional configured column name to read from the model.
     * @return string Printable scalar value, or `-` when the column is disabled or currently `null`.
     */
    private function stringValue(Model $account, ?string $column): string
    {
        if ($column === null) {
            return '-';
        }

        $value = $account->getAttribute($column);

        return $value === null ? '-' : (string) $value;
    }

    /**
     * Convert a configured boolean account column into a printable console value.
     *
     * @param  Model  $account  Account model whose enabled flag should be rendered.
     * @param  string|null  $column  Optional configured enabled column name.
     * @return string `yes` or `no` for configured flags, or `-` when no enabled column is configured.
     */
    private function booleanValue(Model $account, ?string $column): string
    {
        if ($column === null) {
            return '-';
        }

        return (bool) $account->getAttribute($column) ? 'yes' : 'no';
    }
}
