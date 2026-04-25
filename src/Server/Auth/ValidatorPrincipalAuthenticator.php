<?php

declare(strict_types=1);

namespace N3XT0R\LaravelWebdavServer\Server\Auth;

use N3XT0R\LaravelWebdavServer\Contracts\Auth\CredentialValidatorInterface;
use N3XT0R\LaravelWebdavServer\Contracts\Server\PrincipalAuthenticatorInterface;
use N3XT0R\LaravelWebdavServer\Exception\Auth\AuthException;
use N3XT0R\LaravelWebdavServer\Logging\WebDavLoggingService;
use N3XT0R\LaravelWebdavServer\ValueObjects\WebDavPrincipalValueObject;

final readonly class ValidatorPrincipalAuthenticator implements PrincipalAuthenticatorInterface
{
    /**
     * Create the default authenticator that delegates to a credential validator.
     *
     * @param  CredentialValidatorInterface  $validator  Credential validator used to resolve the authenticated principal.
     * @param  WebDavLoggingService  $logger  Package logger used to record authentication outcomes.
     */
    public function __construct(
        private CredentialValidatorInterface $validator,
        private WebDavLoggingService $logger,
    ) {}

    /**
     * Authenticate raw credentials by delegating to the configured credential validator.
     *
     * @param  string  $username  Username extracted from the request.
     * @param  string  $password  Plain-text password extracted from the request.
     * @return WebDavPrincipalValueObject Authenticated principal for the request.
     *
     * @throws AuthException When authentication fails.
     */
    public function authenticate(string $username, string $password): WebDavPrincipalValueObject
    {
        try {
            $principal = $this->validator->validate($username, $password);
        } catch (AuthException $exception) {
            $this->logger->info('WebDAV authentication failed.', [
                'auth' => [
                    'username' => $username,
                    'exception' => $exception::class,
                ],
            ]);

            throw $exception;
        }

        $this->logger->info('WebDAV authentication succeeded.', [
            'auth' => [
                'username' => $username,
                'principal_id' => $principal->id,
            ],
        ]);

        return $principal;
    }
}
