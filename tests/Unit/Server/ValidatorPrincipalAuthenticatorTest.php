<?php

declare(strict_types=1);

namespace N3XT0R\LaravelWebdavServer\Tests\Unit\Server;

use N3XT0R\LaravelWebdavServer\Exception\Auth\InvalidCredentialsException;
use N3XT0R\LaravelWebdavServer\Logging\WebDavLoggingService;
use N3XT0R\LaravelWebdavServer\Server\Auth\ValidatorPrincipalAuthenticator;
use N3XT0R\LaravelWebdavServer\Tests\Fixtures\Auth\ArrayCredentialValidator;
use N3XT0R\LaravelWebdavServer\Tests\Fixtures\Logging\RecordingLogger;
use N3XT0R\LaravelWebdavServer\ValueObjects\WebDavPrincipalValueObject;
use PHPUnit\Framework\TestCase;

final class ValidatorPrincipalAuthenticatorTest extends TestCase
{
    public function test_it_returns_principal_for_valid_credentials(): void
    {
        $principal = new WebDavPrincipalValueObject('42', 'Alice');
        $validator = new ArrayCredentialValidator([
            'alice' => ['secret' => $principal],
        ]);
        $logger = new RecordingLogger;

        $authenticator = new ValidatorPrincipalAuthenticator(
            $validator,
            new WebDavLoggingService($logger, 'stderr', 'info'),
        );

        $this->assertSame($principal, $authenticator->authenticate('alice', 'secret'));
        $this->assertSame([['username' => 'alice', 'password' => 'secret']], $validator->calls);
        $this->assertSame('WebDAV authentication succeeded.', $logger->records[0]['message']);
    }

    public function test_it_throws_when_credentials_are_invalid(): void
    {
        $this->expectException(InvalidCredentialsException::class);

        $validator = new ArrayCredentialValidator;
        $logger = new RecordingLogger;
        $authenticator = new ValidatorPrincipalAuthenticator(
            $validator,
            new WebDavLoggingService($logger, 'stderr', 'info'),
        );

        try {
            $authenticator->authenticate('alice', 'wrong');
        } finally {
            $this->assertSame('WebDAV authentication failed.', $logger->records[0]['message']);
        }
    }
}
