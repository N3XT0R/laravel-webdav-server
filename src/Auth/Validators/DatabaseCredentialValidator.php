<?php

declare(strict_types=1);

namespace N3XT0R\LaravelWebdavServer\Auth\Validators;

use Illuminate\Contracts\Hashing\Hasher;
use N3XT0R\LaravelWebdavServer\Contracts\Auth\CredentialValidatorInterface;
use N3XT0R\LaravelWebdavServer\Contracts\Repositories\AccountRepositoryInterface;
use N3XT0R\LaravelWebdavServer\Exception\Auth\AccountDisabledException;
use N3XT0R\LaravelWebdavServer\Exception\Auth\AccountNotFoundException;
use N3XT0R\LaravelWebdavServer\Exception\Auth\InvalidCredentialsException;
use N3XT0R\LaravelWebdavServer\ValueObjects\WebDavPrincipalValueObject;

final readonly class DatabaseCredentialValidator implements CredentialValidatorInterface
{
    public function __construct(
        protected AccountRepositoryInterface $repository,
        protected Hasher $hasher,
    ) {}

    public function validate(string $username, string $password): WebDavPrincipalValueObject
    {
        try {
            $account = $this->repository->findEnabledByUsername($username);
        } catch (AccountNotFoundException|AccountDisabledException $exception) {
            throw new InvalidCredentialsException(
                message: 'Invalid WebDAV credentials.',
                context: [
                    'auth' => [
                        'username' => $username,
                    ],
                ],
                previous: $exception,
            );
        }

        if (! $this->hasher->check($password, $account->getPasswordHash())) {
            throw new InvalidCredentialsException(
                message: 'Invalid WebDAV credentials.',
                context: [
                    'auth' => [
                        'username' => $username,
                    ],
                ],
            );
        }

        return new WebDavPrincipalValueObject(
            $account->getPrincipalId(),
            $account->getDisplayName(),
            $account->getUser(),
        );
    }
}
