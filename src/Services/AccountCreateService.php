<?php

declare(strict_types=1);

namespace N3XT0R\LaravelWebdavServer\Services;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Hash;
use N3XT0R\LaravelWebdavServer\Contracts\Repositories\AccountManagementRepositoryInterface;
use N3XT0R\LaravelWebdavServer\Exception\Auth\DuplicateUsernameException;

final readonly class AccountCreateService
{
    /**
     * @param  AccountManagementRepositoryInterface  $repository  Repository used to check username uniqueness, build, and persist new accounts.
     */
    public function __construct(
        private AccountManagementRepositoryInterface $repository,
    ) {}

    /**
     * Build and persist a new WebDAV account from the supplied field values.
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
        $mapping = $this->repository->columnMapping();

        if ($this->repository->findByUsername($username) !== null) {
            throw new DuplicateUsernameException("A WebDAV account with username '{$username}' already exists.");
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
}
