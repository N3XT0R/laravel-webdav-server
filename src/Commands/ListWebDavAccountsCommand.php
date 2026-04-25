<?php

declare(strict_types=1);

namespace N3XT0R\LaravelWebdavServer\Commands;

use Illuminate\Console\Command;
use Illuminate\Database\Eloquent\Model;
use N3XT0R\LaravelWebdavServer\DTO\Management\AccountColumnMappingDto;
use N3XT0R\LaravelWebdavServer\Services\AccountManagementService;

final class ListWebDavAccountsCommand extends Command
{
    protected $signature = 'laravel-webdav-server:account:list';

    protected $description = 'List WebDAV accounts from the configured account model.';

    /**
     * List all configured WebDAV accounts in a compact console table.
     *
     * @param  AccountManagementService  $service  Service that provides account listing.
     * @return int Symfony-compatible command exit code.
     */
    public function handle(AccountManagementService $service): int
    {
        $accounts = $service->all();

        if ($accounts->isEmpty()) {
            $this->components->warn('No WebDAV accounts found.');

            return self::SUCCESS;
        }

        $mapping = $service->columnMapping();

        $this->table(
            ['Username', 'Enabled', 'User ID', 'Display Name'],
            $accounts
                ->map(fn (Model $account): array => $this->tableRow($account, $mapping))
                ->all(),
        );

        return self::SUCCESS;
    }

    /**
     * Convert one account model into the row rendered by the list table.
     *
     * @param  Model  $account  Eloquent account model fetched from the configured account store.
     * @param  AccountColumnMappingDto  $mapping  Resolved account column mapping from package config.
     * @return list<string> Ordered row values for username, enabled flag, user ID, and display name.
     */
    private function tableRow(Model $account, AccountColumnMappingDto $mapping): array
    {
        return [
            (string) $account->getAttribute($mapping->usernameColumn),
            $this->booleanValue($account, $mapping->enabledColumn),
            $this->stringValue($account, $mapping->userIdColumn),
            $this->stringValue($account, $mapping->displayNameColumn),
        ];
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
