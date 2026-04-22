<?php

declare(strict_types=1);

namespace N3XT0R\LaravelWebdavServer\Tests\Unit\Server;

use N3XT0R\LaravelWebdavServer\Exception\Auth\InvalidCredentialsException;
use N3XT0R\LaravelWebdavServer\Server\Auth\ValidatorPrincipalAuthenticator;
use N3XT0R\LaravelWebdavServer\Tests\Fixtures\Auth\ArrayCredentialValidator;
use N3XT0R\LaravelWebdavServer\ValueObjects\WebDavPrincipal;
use PHPUnit\Framework\TestCase;

final class ValidatorPrincipalAuthenticatorTest extends TestCase
{
    public function test_it_returns_principal_for_valid_credentials(): void
    {
        $principal = new WebDavPrincipal('42', 'Alice');
        $validator = new ArrayCredentialValidator([
            'alice' => ['secret' => $principal],
        ]);

        $authenticator = new ValidatorPrincipalAuthenticator($validator);

        $this->assertSame($principal, $authenticator->authenticate('alice', 'secret'));
        $this->assertSame([['username' => 'alice', 'password' => 'secret']], $validator->calls);
    }

    public function test_it_throws_when_credentials_are_invalid(): void
    {
        $this->expectException(InvalidCredentialsException::class);

        $validator = new ArrayCredentialValidator();
        $authenticator = new ValidatorPrincipalAuthenticator($validator);

        $authenticator->authenticate('alice', 'wrong');
    }
}
