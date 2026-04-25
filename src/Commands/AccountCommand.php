<?php

declare(strict_types=1);

namespace N3XT0R\LaravelWebdavServer\Commands;

use Illuminate\Console\Command;
use Illuminate\Database\Eloquent\Model;
use N3XT0R\LaravelWebdavServer\DTO\Management\AccountColumnMappingDto;

abstract class AccountCommand extends Command
{
    /**
     * Render a field/value summary table for a single account.
     *
     * @param  Model  $account  Eloquent account model to display.
     * @param  AccountColumnMappingDto  $mapping  Resolved account column mapping from package config.
     */
    protected function renderAccountSummary(Model $account, AccountColumnMappingDto $mapping): void
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
    protected function stringValue(Model $account, ?string $column): string
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
    protected function booleanValue(Model $account, ?string $column): string
    {
        if ($column === null) {
            return '-';
        }

        return (bool) $account->getAttribute($column) ? 'yes' : 'no';
    }
}
