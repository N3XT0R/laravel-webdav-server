<?php

declare(strict_types=1);

namespace N3XT0R\LaravelWebdavServer\Services;

use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Hash;
use N3XT0R\LaravelWebdavServer\Contracts\Repositories\AccountManagementRepositoryInterface;
use N3XT0R\LaravelWebdavServer\DTO\Management\AccountColumnMappingDto;
use N3XT0R\LaravelWebdavServer\DTO\Management\AccountUpdateDto;

final readonly class AccountManagementService
{
    /**
     * @param  AccountManagementRepositoryInterface  $repository  Repository for account model access and persistence.
     */
    public function __construct(
        private AccountManagementRepositoryInterface $repository,
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
     * @throws \InvalidArgumentException When a WebDAV account with the given username already exists.
     */
    public function create(
        string $username,
        string $password,
        ?string $displayName = null,
        mixed $userId = null,
        bool $enabled = true,
    ): Model {
        $mapping = $this->repository->columnMapping();

        if ($this->repository->findByUsername($username) !== null) {
            throw new \InvalidArgumentException("A WebDAV account with username '{$username}' already exists.");
        }

        $account = $this->repository->newModel();
        $account->setAttribute($mapping->usernameColumn, $username);
        $account->setAttribute($mapping->passwordColumn, Hash::make($password));

        if ($mapping->enabledColumn !== null) {
            $account->setAttribute($mapping->enabledColumn, $enabled);
        }

        if ($mapping->userIdColumn !== null && $userId !== null) {
            $account->setAttribute($mapping->userIdColumn, $userId);
        }

        if ($mapping->displayNameColumn !== null) {
            $account->setAttribute(
                $mapping->displayNameColumn,
                ($displayName !== null && trim($displayName) !== '') ? $displayName : $username,
            );
        }

        $this->repository->save($account);

        return $account;
    }

    /**
     * Apply requested field changes to an existing account and persist when at least one change was made.
     *
     * @param  Model  $account  Existing account model instance to update.
     * @param  AccountUpdateDto  $dto  Requested field changes.
     * @return bool `true` when at least one change was applied and persisted, `false` when no fields were changed.
     *
     * @throws \InvalidArgumentException When the requested new username is already taken by another account.
     */
    public function update(Model $account, AccountUpdateDto $dto): bool
    {
        $mapping = $this->repository->columnMapping();
        $changed = false;

        if ($dto->newUsername !== null) {
            $currentUsername = (string) $account->getAttribute($mapping->usernameColumn);

            if ($dto->newUsername !== $currentUsername) {
                if ($this->repository->findByUsername($dto->newUsername) !== null) {
                    throw new \InvalidArgumentException("A WebDAV account with username '{$dto->newUsername}' already exists.");
                }

                $account->setAttribute($mapping->usernameColumn, $dto->newUsername);
                $changed = true;
            }
        }

        if ($dto->password !== null && $dto->password !== '') {
            $account->setAttribute($mapping->passwordColumn, Hash::make($dto->password));
            $changed = true;
        }

        if ($mapping->displayNameColumn !== null) {
            if ($dto->clearDisplayName) {
                $account->setAttribute($mapping->displayNameColumn, null);
                $changed = true;
            } elseif ($dto->displayName !== null) {
                $account->setAttribute($mapping->displayNameColumn, $dto->displayName);
                $changed = true;
            }
        }

        if ($mapping->userIdColumn !== null) {
            if ($dto->clearUserId) {
                $account->setAttribute($mapping->userIdColumn, null);
                $changed = true;
            } elseif ($dto->userId !== null) {
                $account->setAttribute($mapping->userIdColumn, $dto->userId);
                $changed = true;
            }
        }

        if ($mapping->enabledColumn !== null && $dto->enabled !== null) {
            $account->setAttribute($mapping->enabledColumn, $dto->enabled);
            $changed = true;
        }

        if ($changed) {
            $this->repository->save($account);
        }

        return $changed;
    }
}
