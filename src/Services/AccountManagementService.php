<?php

declare(strict_types=1);

namespace N3XT0R\LaravelWebdavServer\Services;

use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;
use N3XT0R\LaravelWebdavServer\Contracts\Repositories\AccountManagementRepositoryInterface;
use N3XT0R\LaravelWebdavServer\DTO\Management\AccountColumnMappingDto;
use N3XT0R\LaravelWebdavServer\DTO\Management\AccountUpdateDto;
use N3XT0R\LaravelWebdavServer\Exception\Auth\DuplicateUsernameException;

final readonly class AccountManagementService
{
    /**
     * @param  AccountManagementRepositoryInterface  $repository  Repository for account model access and persistence.
     * @param  AccountCreateService  $creator  Handles new account creation including uniqueness checks and field mapping.
     * @param  AccountUpdateService  $updater  Handles field-level changes for existing account models.
     */
    public function __construct(
        private AccountManagementRepositoryInterface $repository,
        private AccountCreateService $creator,
        private AccountUpdateService $updater,
    ) {}

    /**
     * Return the configured account column mapping for display and field resolution.
     *
     * @return AccountColumnMappingDto Resolved model class and column names.
     */
    public function columnMapping(): AccountColumnMappingDto
    {
        return $this->repository->columnMapping();
    }

    /**
     * Find one account by username regardless of its enabled state.
     *
     * @param  string  $username  Username to search for.
     * @return Model|null Matching account model, or `null` when not found.
     */
    public function findByUsername(string $username): ?Model
    {
        return $this->repository->findByUsername($username);
    }

    /**
     * Return all accounts ordered by their username column.
     *
     * @return Collection<int, Model> All persisted account records.
     */
    public function all(): Collection
    {
        return $this->repository->all();
    }

    /**
     * Create and persist a new WebDAV account.
     *
     * @param  string  $username  Username for Basic Auth.
     * @param  string  $password  Plain-text password that will be hashed before storage.
     * @param  string|null  $displayName  Optional principal display name; falls back to the username when absent.
     * @param  mixed  $userId  Optional linked Laravel user identifier.
     * @param  bool  $enabled  Whether the account should be active on creation.
     * @return Model Newly created and persisted account model.
     *
     * @throws DuplicateUsernameException When a WebDAV account with the given username already exists.
     */
    public function create(
        string $username,
        string $password,
        ?string $displayName = null,
        mixed $userId = null,
        bool $enabled = true,
    ): Model {
        return $this->creator->create($username, $password, $displayName, $userId, $enabled);
    }

    /**
     * Apply requested field changes to an existing account and persist when at least one change was made.
     *
     * @param  Model  $account  Existing account model instance to update.
     * @param  AccountUpdateDto  $dto  Requested field changes.
     * @return bool `true` when at least one change was applied and persisted, `false` when no fields were changed.
     *
     * @throws DuplicateUsernameException When the requested new username is already taken by another account.
     */
    public function update(Model $account, AccountUpdateDto $dto): bool
    {
        $mapping = $this->repository->columnMapping();
        $changed = $this->updater->apply($account, $mapping, $dto);

        if ($changed) {
            $this->repository->save($account);
        }

        return $changed;
    }
}
