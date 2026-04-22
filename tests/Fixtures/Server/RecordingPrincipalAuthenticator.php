<?php

declare(strict_types=1);

namespace N3XT0R\LaravelWebdavServer\Tests\Fixtures\Server;

use N3XT0R\LaravelWebdavServer\Contracts\Server\PrincipalAuthenticatorInterface;
use N3XT0R\LaravelWebdavServer\ValueObjects\WebDavPrincipal;

final class RecordingPrincipalAuthenticator implements PrincipalAuthenticatorInterface
{
    /** @var list<array{username:string,password:string}> */
    public array $calls = [];

    public function __construct(
        private readonly WebDavPrincipal $principal,
    ) {}

    public function authenticate(string $username, string $password): WebDavPrincipal
    {
        $this->calls[] = [
            'username' => $username,
            'password' => $password,
        ];

        return $this->principal;
    }
}
