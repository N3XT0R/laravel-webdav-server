<?php

declare(strict_types=1);

namespace N3XT0R\LaravelWebdavServer\Auth;

use Illuminate\Contracts\Hashing\Hasher;
use N3XT0R\LaravelWebdavServer\Contracts\Auth\CredentialValidatorInterface;
use N3XT0R\LaravelWebdavServer\Contracts\Repositories\WebDavAccountRepositoryInterface;
use N3XT0R\LaravelWebdavServer\ValueObjects\WebDavPrincipal;

final readonly class DatabaseCredentialValidator implements CredentialValidatorInterface
{
    public function __construct(
        protected WebDavAccountRepositoryInterface $repository,
        protected Hasher $hasher,
    ) {
    }

    public function validate(string $username, string $password): ?WebDavPrincipal
    {
        $account = $this->repository->findEnabledByUsername($username);

        if ($account === null) {
            return null;
        }

        if (!$this->hasher->check($password, $account->getPasswordHash())) {
            return null;
        }

        return new WebDavPrincipal(
            $account->getPrincipalId(),
            $account->getDisplayName(),
        );
    }
}
