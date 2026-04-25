<?php

declare(strict_types=1);

namespace N3XT0R\LaravelWebdavServer\Commands;

use Illuminate\Console\Command;
use Illuminate\Database\Eloquent\Model;
use N3XT0R\LaravelWebdavServer\Commands\Support\AccountModelConfiguration;
use N3XT0R\LaravelWebdavServer\Commands\Support\AccountModelResolver;

final class ShowWebDavAccountCommand extends Command
{
    protected $signature = 'laravel-webdav-server:account:show
        {username : Username of the WebDAV account that should be shown.}';

    protected $description = 'Show one WebDAV account from the configured account model.';

    /**
     * Show one configured WebDAV account and its relevant package-facing fields.
     *
     * @param  AccountModelResolver  $accountResolver  Helper that resolves the configured account model and column mapping.
     * @return int Symfony-compatible command exit code.
     */
    public function handle(AccountModelResolver $accountResolver): int
    {
        $username = (string) $this->argument('username');
        $account = $accountResolver->findByUsername($username);

        if ($account === null) {
            $this->components->error("No WebDAV account found for username '{$username}'.");

            return self::FAILURE;
        }

        $this->table(
            ['Field', 'Value'],
            $this->detailsRows($account, $accountResolver->configuration()),
        );

        return self::SUCCESS;
    }

    /**
     * Build the detail rows rendered by the show command.
     *
     * @param  Model  $account  Eloquent account model that matched the requested username.
     * @param  AccountModelConfiguration  $configuration  Resolved account column mapping from package config.
     * @return list<list<string>> Ordered field/value pairs for the shown account.
     */
    private function detailsRows(Model $account, AccountModelConfiguration $configuration): array
    {
        return [
            ['model', $account::class],
            ['username', (string) $account->getAttribute($configuration->usernameColumn)],
            ['enabled', $this->booleanValue($account, $configuration->enabledColumn)],
            ['user_id', $this->stringValue($account, $configuration->userIdColumn)],
            ['display_name', $this->stringValue($account, $configuration->displayNameColumn)],
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
