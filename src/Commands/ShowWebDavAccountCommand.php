<?php

declare(strict_types=1);

namespace N3XT0R\LaravelWebdavServer\Commands;

use Illuminate\Console\Command;
use Illuminate\Database\Eloquent\Model;
use N3XT0R\LaravelWebdavServer\DTO\Management\AccountColumnMappingDto;
use N3XT0R\LaravelWebdavServer\Services\AccountManagementService;

final class ShowWebDavAccountCommand extends Command
{
    protected $signature = 'laravel-webdav-server:account:show
        {username : Username of the WebDAV account that should be shown.}';

    protected $description = 'Show one WebDAV account from the configured account model.';

    /**
     * Show one configured WebDAV account and its relevant package-facing fields.
     *
     * @param  AccountManagementService  $service  Service that provides account lookup.
     * @return int Symfony-compatible command exit code.
     */
    public function handle(AccountManagementService $service): int
    {
        $username = (string) $this->argument('username');
        $account = $service->findByUsername($username);

        if ($account === null) {
            $this->components->error("No WebDAV account found for username '{$username}'.");

            return self::FAILURE;
        }

        $mapping = $service->columnMapping();

        $this->table(
            ['Field', 'Value'],
            $this->detailsRows($account, $mapping),
        );

        return self::SUCCESS;
    }

    /**
     * Build the detail rows rendered by the show command.
     *
     * @param  Model  $account  Eloquent account model that matched the requested username.
     * @param  AccountColumnMappingDto  $mapping  Resolved account column mapping from package config.
     * @return list<list<string>> Ordered field/value pairs for the shown account.
     */
    private function detailsRows(Model $account, AccountColumnMappingDto $mapping): array
    {
        return [
            ['model', $account::class],
            ['username', (string) $account->getAttribute($mapping->usernameColumn)],
            ['enabled', $this->booleanValue($account, $mapping->enabledColumn)],
            ['user_id', $this->stringValue($account, $mapping->userIdColumn)],
            ['display_name', $this->stringValue($account, $mapping->displayNameColumn)],
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
