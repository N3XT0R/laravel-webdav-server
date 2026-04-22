<?php

declare(strict_types=1);

namespace N3XT0R\LaravelWebdavServer\Tests\Fixtures\Auth;

use N3XT0R\LaravelWebdavServer\Contracts\Auth\CredentialValidatorInterface;
use N3XT0R\LaravelWebdavServer\ValueObjects\WebDavPrincipal;

final class ArrayCredentialValidator implements CredentialValidatorInterface
{
    /** @var list<array{username:string,password:string}> */
    public array $calls = [];

    /**
     * @param  array<string, array<string, WebDavPrincipal|null>>  $credentials
     */
    public function __construct(
        private array $credentials = [],
    ) {}

    public function validate(string $username, string $password): ?WebDavPrincipal
    {
        $this->calls[] = [
            'username' => $username,
            'password' => $password,
        ];

        return $this->credentials[$username][$password] ?? null;
    }
}
