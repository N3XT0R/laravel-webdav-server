<?php

declare(strict_types=1);

namespace N3XT0R\LaravelWebdavServer\Commands;

use Illuminate\Console\Command;
use Illuminate\Database\Eloquent\Model;
use N3XT0R\LaravelWebdavServer\DTO\Management\AccountColumnMappingDto;
use N3XT0R\LaravelWebdavServer\Services\AccountManagementService;

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
     * @param  AccountManagementService  $service  Service that handles account creation business logic.
     * @return int Symfony-compatible command exit code.
     */
    public function handle(AccountManagementService $service): int
    {
        try {
            $account = $service->create(
                username: (string) $this->argument('username'),
                password: (string) $this->argument('password'),
                displayName: $this->option('display-name'),
                userId: $this->option('user-id'),
                enabled: ! (bool) $this->option('disabled'),
            );
        } catch (\InvalidArgumentException $e) {
            $this->components->error($e->getMessage());

            return self::FAILURE;
        }

        $this->components->info("Created WebDAV account '{$this->argument('username')}'.");
        $this->renderAccountSummary($account, $service->columnMapping());

        return self::SUCCESS;
    }

    /**
     * Render a concise summary of the created account for console users.
     *
     * @param  Model  $account  Newly persisted Eloquent account model instance.
     * @param  AccountColumnMappingDto  $mapping  Resolved account column mapping from package config.
     */
    private function renderAccountSummary(Model $account, AccountColumnMappingDto $mapping): void
    {
        $this->table(
            ['Field', 'Value'],
            [
                ['username', (string) $account->getAttribute($mapping->usernameColumn)],
                ['enabled', $this->booleanValue($account, $mapping->enabledColumn)],
                ['user_id', $this->stringValue($account, $mapping->userIdColumn)],
                ['display_name', $this->stringValue($account, $mapping->displayNameColumn)],
            ],
        );
    }

    /**
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
