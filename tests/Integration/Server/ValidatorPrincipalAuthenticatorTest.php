<?php

declare(strict_types=1);

namespace N3XT0R\LaravelWebdavServer\Tests\Integration\Server;

use N3XT0R\LaravelWebdavServer\Contracts\Auth\CredentialValidatorInterface;
use N3XT0R\LaravelWebdavServer\Exception\Auth\InvalidCredentialsException;
use N3XT0R\LaravelWebdavServer\Server\Auth\ValidatorPrincipalAuthenticator;
use N3XT0R\LaravelWebdavServer\Tests\TestCase;
use N3XT0R\LaravelWebdavServer\ValueObjects\WebDavPrincipal;

final class ValidatorPrincipalAuthenticatorTest extends TestCase
{
    public function test_it_returns_principal_for_valid_credentials(): void
    {
        $principal = new WebDavPrincipal('42', 'Alice');

        $validator = $this->createMock(CredentialValidatorInterface::class);
        $validator->expects($this->once())
            ->method('validate')
            ->with('alice', 'secret')
            ->willReturn($principal);

        $authenticator = new ValidatorPrincipalAuthenticator($validator);

        $this->assertSame($principal, $authenticator->authenticate('alice', 'secret'));
    }

    public function test_it_throws_when_credentials_are_invalid(): void
    {
        $this->expectException(InvalidCredentialsException::class);

        $validator = $this->createMock(CredentialValidatorInterface::class);
        $validator->expects($this->once())
            ->method('validate')
            ->with('alice', 'wrong')
            ->willReturn(null);

        $authenticator = new ValidatorPrincipalAuthenticator($validator);
        $authenticator->authenticate('alice', 'wrong');
    }
}
