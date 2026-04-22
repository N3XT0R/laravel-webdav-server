<?php

declare(strict_types=1);

namespace N3XT0R\LaravelWebdavServer\Auth\Validators;

use Illuminate\Contracts\Hashing\Hasher;
use N3XT0R\LaravelWebdavServer\Contracts\Auth\CredentialValidatorInterface;
use N3XT0R\LaravelWebdavServer\Contracts\Repositories\WebDavAccountRepositoryInterface;
use N3XT0R\LaravelWebdavServer\ValueObjects\WebDavPrincipalValueObject;

final readonly class DatabaseCredentialValidator implements CredentialValidatorInterface
{
    public function __construct(
        protected WebDavAccountRepositoryInterface $repository,
        protected Hasher $hasher,
    ) {}

    public function validate(string $username, string $password): ?WebDavPrincipalValueObject
    {
        $account = $this->repository->findEnabledByUsername($username);

        if ($account === null) {
            return null;
        }

        if (! $this->hasher->check($password, $account->getPasswordHash())) {
            return null;
        }

        return new WebDavPrincipalValueObject(
            $account->getPrincipalId(),
            $account->getDisplayName(),
            $account->getUser()
        );
    }
}
