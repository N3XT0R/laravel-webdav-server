<?php

declare(strict_types=1);

namespace N3XT0R\LaravelWebdavServer\Commands;

use Illuminate\Console\Command;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Hash;
use N3XT0R\LaravelWebdavServer\Commands\Support\AccountModelConfiguration;
use N3XT0R\LaravelWebdavServer\Commands\Support\AccountModelResolver;

final class UpdateWebDavAccountCommand extends Command
{
    protected $signature = 'laravel-webdav-server:account:update
        {username : Username of the WebDAV account that should be updated.}
        {--new-username= : Replace the current Basic Auth username.}
        {--password= : Replace the stored password with a newly hashed password.}
        {--display-name= : Replace the stored display name.}
        {--clear-display-name : Clear the stored display name.}
        {--user-id= : Replace the linked Laravel user identifier.}
        {--clear-user-id : Clear the linked Laravel user identifier.}
        {--enable : Mark the account as enabled.}
        {--disable : Mark the account as disabled.}';

    protected $description = 'Update an existing WebDAV account in the configured account model.';

    /**
     * Update one configured WebDAV account using additive CLI options.
     *
     * @param  AccountModelResolver  $accountResolver  Helper that resolves the configured account model and column mapping.
     * @return int Symfony-compatible command exit code.
     */
    public function handle(AccountModelResolver $accountResolver): int
    {
        if ($this->hasConflictingOptions()) {
            return self::FAILURE;
        }

        $configuration = $accountResolver->configuration();
        $account = $accountResolver->findByUsername((string) $this->argument('username'));

        if ($account === null) {
            $this->components->error("No WebDAV account found for username '{$this->argument('username')}'.");

            return self::FAILURE;
        }

        if (! $this->applyChanges($account, $accountResolver, $configuration)) {
            return self::FAILURE;
        }

        $account->save();

        $this->components->info("Updated WebDAV account '{$account->getAttribute($configuration->usernameColumn)}'.");
        $this->renderAccountSummary($account, $configuration);

        return self::SUCCESS;
    }

    /**
     * Validate mutually exclusive option combinations before mutating an account record.
     *
     * @return bool `true` when conflicting options were detected and reported to the console.
     */
    private function hasConflictingOptions(): bool
    {
        if ((bool) $this->option('enable') && (bool) $this->option('disable')) {
            $this->components->error('Use either --enable or --disable, not both.');

            return true;
        }

        if ($this->option('user-id') !== null && (bool) $this->option('clear-user-id')) {
            $this->components->error('Use either --user-id or --clear-user-id, not both.');

            return true;
        }

        if ($this->option('display-name') !== null && (bool) $this->option('clear-display-name')) {
            $this->components->error('Use either --display-name or --clear-display-name, not both.');

            return true;
        }

        return false;
    }

    /**
     * Apply all requested field changes to the target account model.
     *
     * @param  Model  $account  Existing account model instance that should be mutated.
     * @param  AccountModelResolver  $accountResolver  Helper used for duplicate username checks.
     * @param  AccountModelConfiguration  $configuration  Resolved account column mapping from package config.
     * @return bool `true` when at least one change was applied successfully, otherwise `false`.
     */
    private function applyChanges(
        Model $account,
        AccountModelResolver $accountResolver,
        AccountModelConfiguration $configuration,
    ): bool {
        $changesApplied = false;
        $changesApplied = $this->applyUsernameChange($account, $accountResolver, $configuration) || $changesApplied;
        $changesApplied = $this->applyPasswordChange($account, $configuration) || $changesApplied;
        $changesApplied = $this->applyDisplayNameChange($account, $configuration) || $changesApplied;
        $changesApplied = $this->applyUserIdChange($account, $configuration) || $changesApplied;
        $changesApplied = $this->applyEnabledChange($account, $configuration) || $changesApplied;

        if ($changesApplied) {
            return true;
        }

        $this->components->warn('No changes requested.');

        return false;
    }

    /**
     * Update the configured username column when a new username was requested.
     *
     * @param  Model  $account  Existing account model instance that should be mutated.
     * @param  AccountModelResolver  $accountResolver  Helper used for duplicate username checks.
     * @param  AccountModelConfiguration  $configuration  Resolved account column mapping from package config.
     * @return bool `true` when the username changed, otherwise `false`.
     */
    private function applyUsernameChange(
        Model $account,
        AccountModelResolver $accountResolver,
        AccountModelConfiguration $configuration,
    ): bool {
        $newUsername = $this->option('new-username');

        if (! is_string($newUsername) || trim($newUsername) === '') {
            return false;
        }

        $currentUsername = (string) $account->getAttribute($configuration->usernameColumn);

        if ($newUsername === $currentUsername) {
            return false;
        }

        if ($accountResolver->findByUsername($newUsername) !== null) {
            $this->components->error("A WebDAV account with username '{$newUsername}' already exists.");

            return false;
        }

        $account->setAttribute($configuration->usernameColumn, $newUsername);

        return true;
    }

    /**
     * Update the configured password column when a replacement password was requested.
     *
     * @param  Model  $account  Existing account model instance that should be mutated.
     * @param  AccountModelConfiguration  $configuration  Resolved account column mapping from package config.
     * @return bool `true` when the password changed, otherwise `false`.
     */
    private function applyPasswordChange(Model $account, AccountModelConfiguration $configuration): bool
    {
        $password = $this->option('password');

        if (! is_string($password) || $password === '') {
            return false;
        }

        $account->setAttribute($configuration->passwordColumn, Hash::make($password));

        return true;
    }

    /**
     * Update or clear the configured display-name column when requested.
     *
     * @param  Model  $account  Existing account model instance that should be mutated.
     * @param  AccountModelConfiguration  $configuration  Resolved account column mapping from package config.
     * @return bool `true` when the display name changed, otherwise `false`.
     */
    private function applyDisplayNameChange(Model $account, AccountModelConfiguration $configuration): bool
    {
        if ($configuration->displayNameColumn === null) {
            return false;
        }

        if ((bool) $this->option('clear-display-name')) {
            $account->setAttribute($configuration->displayNameColumn, null);

            return true;
        }

        $displayName = $this->option('display-name');

        if (! is_string($displayName)) {
            return false;
        }

        $account->setAttribute($configuration->displayNameColumn, $displayName);

        return true;
    }

    /**
     * Update or clear the configured linked-user column when requested.
     *
     * @param  Model  $account  Existing account model instance that should be mutated.
     * @param  AccountModelConfiguration  $configuration  Resolved account column mapping from package config.
     * @return bool `true` when the linked user changed, otherwise `false`.
     */
    private function applyUserIdChange(Model $account, AccountModelConfiguration $configuration): bool
    {
        if ($configuration->userIdColumn === null) {
            return false;
        }

        if ((bool) $this->option('clear-user-id')) {
            $account->setAttribute($configuration->userIdColumn, null);

            return true;
        }

        if ($this->option('user-id') === null) {
            return false;
        }

        $account->setAttribute($configuration->userIdColumn, $this->option('user-id'));

        return true;
    }

    /**
     * Update the configured enabled flag when one of the state options was requested.
     *
     * @param  Model  $account  Existing account model instance that should be mutated.
     * @param  AccountModelConfiguration  $configuration  Resolved account column mapping from package config.
     * @return bool `true` when the enabled flag changed, otherwise `false`.
     */
    private function applyEnabledChange(Model $account, AccountModelConfiguration $configuration): bool
    {
        if ($configuration->enabledColumn === null) {
            return false;
        }

        if ((bool) $this->option('enable')) {
            $account->setAttribute($configuration->enabledColumn, true);

            return true;
        }

        if ((bool) $this->option('disable')) {
            $account->setAttribute($configuration->enabledColumn, false);

            return true;
        }

        return false;
    }

    /**
     * Render a concise summary of the updated account for console users.
     *
     * @param  Model  $account  Persisted Eloquent account model after the update completed.
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
