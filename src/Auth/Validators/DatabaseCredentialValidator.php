<?php

declare(strict_types=1);

namespace N3XT0R\LaravelWebdavServer\Auth\Validators;

use Illuminate\Contracts\Hashing\Hasher;
use N3XT0R\LaravelWebdavServer\Contracts\Auth\CredentialValidatorInterface;
use N3XT0R\LaravelWebdavServer\Contracts\Repositories\AccountRepositoryInterface;
use N3XT0R\LaravelWebdavServer\Exception\Auth\AccountDisabledException;
use N3XT0R\LaravelWebdavServer\Exception\Auth\AccountNotFoundException;
use N3XT0R\LaravelWebdavServer\Exception\Auth\InvalidCredentialsException;
use N3XT0R\LaravelWebdavServer\Logging\WebDavLoggingService;
use N3XT0R\LaravelWebdavServer\ValueObjects\WebDavPrincipalValueObject;

final readonly class DatabaseCredentialValidator implements CredentialValidatorInterface
{
    /**
     * Create the default database-backed credential validator.
     *
     * @param  AccountRepositoryInterface  $repository  Repository used to resolve enabled account records by username.
     * @param  Hasher  $hasher  Laravel hasher used to verify the supplied password against the stored hash.
     * @param  WebDavLoggingService  $logger  Package logger used to trace validator internals without logging secrets.
     */
    public function __construct(
        protected AccountRepositoryInterface $repository,
        protected Hasher $hasher,
        protected WebDavLoggingService $logger,
    ) {}

    /**
     * Validate database-backed credentials and resolve the authenticated WebDAV principal.
     *
     * @param  string  $username  Username extracted from the Basic Auth credentials.
     * @param  string  $password  Plain-text password extracted from the Basic Auth credentials.
     * @return WebDavPrincipalValueObject Authenticated principal built from the resolved account record.
     *
     * @throws InvalidCredentialsException When the account cannot be resolved or the password check fails.
     */
    public function validate(string $username, string $password): WebDavPrincipalValueObject
    {
        try {
            $account = $this->repository->findEnabledByUsername($username);
        } catch (AccountNotFoundException|AccountDisabledException $exception) {
            $this->logger->debug('WebDAV account lookup failed during credential validation.', [
                'auth' => [
                    'username' => $username,
                    'exception' => $exception::class,
                ],
            ]);

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

        $this->logger->debug('Resolved WebDAV account for credential validation.', [
            'auth' => [
                'username' => $username,
                'principal_id' => $account->getPrincipalId(),
            ],
        ]);

        if (! $this->hasher->check($password, $account->getPasswordHash())) {
            $this->logger->debug('WebDAV password verification failed.', [
                'auth' => [
                    'username' => $username,
                    'principal_id' => $account->getPrincipalId(),
                ],
            ]);

            throw new InvalidCredentialsException(
                message: 'Invalid WebDAV credentials.',
                context: [
                    'auth' => [
                        'username' => $username,
                    ],
                ],
            );
        }

        $this->logger->debug('WebDAV password verification succeeded.', [
            'auth' => [
                'username' => $username,
                'principal_id' => $account->getPrincipalId(),
            ],
        ]);

        return new WebDavPrincipalValueObject(
            $account->getPrincipalId(),
            $account->getDisplayName(),
            $account->getUser(),
        );
    }
}
