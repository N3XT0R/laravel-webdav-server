<?php

declare(strict_types=1);

namespace N3XT0R\LaravelWebdavServer\Services;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Hash;
use N3XT0R\LaravelWebdavServer\Contracts\Repositories\AccountManagementRepositoryInterface;
use N3XT0R\LaravelWebdavServer\DTO\Management\AccountColumnMappingDto;
use N3XT0R\LaravelWebdavServer\DTO\Management\AccountUpdateDto;
use N3XT0R\LaravelWebdavServer\Exception\Auth\DuplicateUsernameException;

final readonly class AccountUpdateApplier
{
    /**
     * @param  AccountManagementRepositoryInterface  $repository  Repository used to check username uniqueness during updates.
     */
    public function __construct(
        private AccountManagementRepositoryInterface $repository,
    ) {}

    /**
     * Apply all requested field changes from the DTO to the account model.
     *
     * @param  Model  $account  Account model instance to mutate.
     * @param  AccountColumnMappingDto  $mapping  Resolved column mapping for the configured account model.
     * @param  AccountUpdateDto  $dto  Requested field changes.
     * @return bool `true` when at least one field was changed, `false` when the model was not mutated.
     *
     * @throws DuplicateUsernameException When the requested new username is already taken by another account.
     */
    public function apply(Model $account, AccountColumnMappingDto $mapping, AccountUpdateDto $dto): bool
    {
        return in_array(true, [
            $this->applyUsernameChange($account, $mapping, $dto),
            $this->applyPasswordChange($account, $mapping, $dto),
            $this->applyDisplayNameChange($account, $mapping, $dto),
            $this->applyUserIdChange($account, $mapping, $dto),
            $this->applyEnabledChange($account, $mapping, $dto),
        ], true);
    }

    private function applyUsernameChange(Model $account, AccountColumnMappingDto $mapping, AccountUpdateDto $dto): bool
    {
        if ($dto->newUsername === null) {
            return false;
        }

        $currentUsername = (string) $account->getAttribute($mapping->usernameColumn);

        if ($dto->newUsername === $currentUsername) {
            return false;
        }

        if ($this->repository->findByUsername($dto->newUsername) !== null) {
            throw new DuplicateUsernameException("A WebDAV account with username '{$dto->newUsername}' already exists.");
        }

        $account->setAttribute($mapping->usernameColumn, $dto->newUsername);

        return true;
    }

    private function applyPasswordChange(Model $account, AccountColumnMappingDto $mapping, AccountUpdateDto $dto): bool
    {
        if ($dto->password === null || $dto->password === '') {
            return false;
        }

        $account->setAttribute($mapping->passwordColumn, Hash::make($dto->password));

        return true;
    }

    private function applyDisplayNameChange(Model $account, AccountColumnMappingDto $mapping, AccountUpdateDto $dto): bool
    {
        if ($mapping->displayNameColumn === null) {
            return false;
        }

        if ($dto->clearDisplayName) {
            $account->setAttribute($mapping->displayNameColumn, null);

            return true;
        }

        if ($dto->displayName !== null) {
            $account->setAttribute($mapping->displayNameColumn, $dto->displayName);

            return true;
        }

        return false;
    }

    private function applyUserIdChange(Model $account, AccountColumnMappingDto $mapping, AccountUpdateDto $dto): bool
    {
        if ($mapping->userIdColumn === null) {
            return false;
        }

        if ($dto->clearUserId) {
            $account->setAttribute($mapping->userIdColumn, null);

            return true;
        }

        if ($dto->userId !== null) {
            $account->setAttribute($mapping->userIdColumn, $dto->userId);

            return true;
        }

        return false;
    }

    private function applyEnabledChange(Model $account, AccountColumnMappingDto $mapping, AccountUpdateDto $dto): bool
    {
        if ($mapping->enabledColumn === null || $dto->enabled === null) {
            return false;
        }

        $account->setAttribute($mapping->enabledColumn, $dto->enabled);

        return true;
    }
}
