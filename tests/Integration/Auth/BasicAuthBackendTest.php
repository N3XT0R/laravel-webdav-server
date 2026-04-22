<?php

declare(strict_types=1);

namespace N3XT0R\LaravelWebdavServer\Tests\Integration\Auth;

use N3XT0R\LaravelWebdavServer\Auth\Backends\BasicAuthBackend;
use N3XT0R\LaravelWebdavServer\Contracts\Auth\CredentialValidatorInterface;
use N3XT0R\LaravelWebdavServer\Tests\TestCase;
use N3XT0R\LaravelWebdavServer\ValueObjects\WebDavPrincipal;

final class BasicAuthBackendTest extends TestCase
{
    public function test_get_principal_returns_null_before_any_authentication(): void
    {
        $validator = $this->createMock(CredentialValidatorInterface::class);
        $backend = new BasicAuthBackend($validator);

        $this->assertNull($backend->getPrincipal());
    }

    public function test_get_realm_returns_default_realm(): void
    {
        $validator = $this->createMock(CredentialValidatorInterface::class);
        $backend = new BasicAuthBackend($validator);

        $this->assertSame('Laravel WebDAV', $backend->getRealm());
    }

    public function test_get_realm_returns_custom_realm(): void
    {
        $validator = $this->createMock(CredentialValidatorInterface::class);
        $backend = new BasicAuthBackend($validator, 'My Custom Realm');

        $this->assertSame('My Custom Realm', $backend->getRealm());
    }

    public function test_validate_user_pass_returns_true_for_valid_credentials(): void
    {
        $principal = new WebDavPrincipal('42', 'Alice');

        $validator = $this->createMock(CredentialValidatorInterface::class);
        $validator->expects($this->once())
            ->method('validate')
            ->with('alice', 'secret')
            ->willReturn($principal);

        $backend = new BasicAuthBackend($validator);

        $method = new \ReflectionMethod($backend, 'validateUserPass');
        $result = $method->invoke($backend, 'alice', 'secret');

        $this->assertTrue($result);
    }

    public function test_validate_user_pass_stores_principal_on_success(): void
    {
        $principal = new WebDavPrincipal('42', 'Alice');

        $validator = $this->createMock(CredentialValidatorInterface::class);
        $validator->method('validate')->willReturn($principal);

        $backend = new BasicAuthBackend($validator);

        $method = new \ReflectionMethod($backend, 'validateUserPass');
        $method->invoke($backend, 'alice', 'secret');

        $this->assertSame($principal, $backend->getPrincipal());
    }

    public function test_validate_user_pass_returns_false_for_invalid_credentials(): void
    {
        $validator = $this->createMock(CredentialValidatorInterface::class);
        $validator->expects($this->once())
            ->method('validate')
            ->with('alice', 'wrong')
            ->willReturn(null);

        $backend = new BasicAuthBackend($validator);

        $method = new \ReflectionMethod($backend, 'validateUserPass');
        $result = $method->invoke($backend, 'alice', 'wrong');

        $this->assertFalse($result);
    }

    public function test_principal_remains_null_after_failed_authentication(): void
    {
        $validator = $this->createMock(CredentialValidatorInterface::class);
        $validator->method('validate')->willReturn(null);

        $backend = new BasicAuthBackend($validator);

        $method = new \ReflectionMethod($backend, 'validateUserPass');
        $method->invoke($backend, 'alice', 'wrong');

        $this->assertNull($backend->getPrincipal());
    }
}
