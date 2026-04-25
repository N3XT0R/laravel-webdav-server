<?php

declare(strict_types=1);

namespace N3XT0R\LaravelWebdavServer\Commands;

use Illuminate\Console\Command;
use Illuminate\Database\Eloquent\Model;
use N3XT0R\LaravelWebdavServer\Commands\Support\AccountModelConfiguration;
use N3XT0R\LaravelWebdavServer\Commands\Support\AccountModelResolver;

final class ListWebDavAccountsCommand extends Command
{
    protected $signature = 'laravel-webdav-server:account:list';

    protected $description = 'List WebDAV accounts from the configured account model.';

    /**
     * List all configured WebDAV accounts in a compact console table.
     *
     * @param  AccountModelResolver  $accountResolver  Helper that resolves the configured account model and column mapping.
     * @return int Symfony-compatible command exit code.
     */
    public function handle(AccountModelResolver $accountResolver): int
    {
        $configuration = $accountResolver->configuration();
        $accounts = $accountResolver->query()
            ->orderBy($configuration->usernameColumn)
            ->get();

        if ($accounts->isEmpty()) {
            $this->components->warn('No WebDAV accounts found.');

            return self::SUCCESS;
        }

        $this->table(
            ['Username', 'Enabled', 'User ID', 'Display Name'],
            $accounts
                ->map(fn (Model $account): array => $this->tableRow($account, $configuration))
                ->all(),
        );

        return self::SUCCESS;
    }

    /**
     * Convert one account model into the row rendered by the list table.
     *
     * @param  Model  $account  Eloquent account model fetched from the configured account store.
     * @param  AccountModelConfiguration  $configuration  Resolved account column mapping from package config.
     * @return list<string> Ordered row values for username, enabled flag, user ID, and display name.
     */
    private function tableRow(Model $account, AccountModelConfiguration $configuration): array
    {
        return [
            (string) $account->getAttribute($configuration->usernameColumn),
            $this->booleanValue($account, $configuration->enabledColumn),
            $this->stringValue($account, $configuration->userIdColumn),
            $this->stringValue($account, $configuration->displayNameColumn),
        ];
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
