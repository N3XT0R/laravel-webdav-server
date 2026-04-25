<?php

declare(strict_types=1);

namespace N3XT0R\LaravelWebdavServer\Contracts\Repositories;

use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;
use N3XT0R\LaravelWebdavServer\DTO\Management\AccountColumnMappingDto;
use N3XT0R\LaravelWebdavServer\Exception\Auth\InvalidAccountConfigurationException;

interface AccountManagementRepositoryInterface
{
    /**
     * Resolve the configured account model column mapping.
     *
     * @return AccountColumnMappingDto Resolved model class and column names for the configured account store.
     *
     * @throws InvalidAccountConfigurationException When `webdav-server.auth.account_model` is missing or invalid.
     */
    public function columnMapping(): AccountColumnMappingDto;

    /**
     * Instantiate a fresh configured WebDAV account model without persisting it.
     *
     * @return Model New Eloquent model instance ready to be filled and saved.
     *
     * @throws InvalidAccountConfigurationException When the configured account model is invalid.
     */
    public function newModel(): Model;

    /**
     * Find one account by its username regardless of its enabled state.
     *
     * @param  string  $username  Username value to look up in the configured account model.
     * @return Model|null Matching account model, or `null` when no record exists for the given username.
     *
     * @throws InvalidAccountConfigurationException When the configured account model is invalid.
     */
    public function findByUsername(string $username): ?Model;

    /**
     * Return all accounts from the configured model ordered by their username column.
     *
     * @return Collection<int, Model> All persisted account records in ascending username order.
     *
     * @throws InvalidAccountConfigurationException When the configured account model is invalid.
     */
    public function all(): Collection;

    /**
     * Persist an account model instance.
     *
     * @param  Model  $account  Filled account model instance to persist.
     */
    public function save(Model $account): void;
}
