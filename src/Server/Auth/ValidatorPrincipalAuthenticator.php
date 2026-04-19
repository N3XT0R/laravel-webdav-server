<?php

declare(strict_types=1);

namespace N3XT0R\LaravelWebdavServer\Server\Auth;

use N3XT0R\LaravelWebdavServer\Contracts\Auth\CredentialValidatorInterface;
use N3XT0R\LaravelWebdavServer\Contracts\Server\PrincipalAuthenticatorInterface;
use N3XT0R\LaravelWebdavServer\Exception\Auth\InvalidCredentialsException;
use N3XT0R\LaravelWebdavServer\ValueObjects\WebDavPrincipal;

final readonly class ValidatorPrincipalAuthenticator implements PrincipalAuthenticatorInterface
{
    public function __construct(
        private CredentialValidatorInterface $validator,
    ) {}

    public function authenticate(string $username, string $password): WebDavPrincipal
    {
        $principal = $this->validator->validate($username, $password);

        if ($principal === null) {
            throw new InvalidCredentialsException(
                message: 'Invalid WebDAV credentials.',
                context: [
                    'auth' => [
                        'username' => $username,
                    ],
                ],
            );
        }

        return $principal;
    }
}


