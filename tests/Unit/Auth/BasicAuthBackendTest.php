<?php

declare(strict_types=1);

namespace N3XT0R\LaravelWebdavServer\Tests\Unit\Auth;

use N3XT0R\LaravelWebdavServer\Auth\Backends\BasicAuthBackend;
use N3XT0R\LaravelWebdavServer\Tests\Fixtures\Auth\ArrayCredentialValidator;
use N3XT0R\LaravelWebdavServer\Tests\TestCase;
use N3XT0R\LaravelWebdavServer\ValueObjects\WebDavPrincipal;
use ReflectionMethod;

final class BasicAuthBackendTest extends TestCase
{
    public function test_get_principal_returns_null_before_any_authentication(): void
    {
        $backend = new BasicAuthBackend(new ArrayCredentialValidator());

        $this->assertNull($backend->getPrincipal());
    }

    public function test_get_realm_returns_default_realm(): void
    {
        $backend = new BasicAuthBackend(new ArrayCredentialValidator());

        $this->assertSame('Laravel WebDAV', $backend->getRealm());
    }

    public function test_get_realm_returns_custom_realm(): void
    {
        $backend = new BasicAuthBackend(new ArrayCredentialValidator(), 'My Custom Realm');

        $this->assertSame('My Custom Realm', $backend->getRealm());
    }

    public function test_validate_user_pass_returns_true_for_valid_credentials(): void
    {
        $principal = new WebDavPrincipal('42', 'Alice');
        $backend = new BasicAuthBackend(new ArrayCredentialValidator([
            'alice' => ['secret' => $principal],
        ]));

        $result = $this->invokeValidateUserPass($backend, 'alice', 'secret');

        $this->assertTrue($result);
    }

    public function test_validate_user_pass_stores_principal_on_success(): void
    {
        $principal = new WebDavPrincipal('42', 'Alice');
        $backend = new BasicAuthBackend(new ArrayCredentialValidator([
            'alice' => ['secret' => $principal],
        ]));

        $this->invokeValidateUserPass($backend, 'alice', 'secret');

        $this->assertSame($principal, $backend->getPrincipal());
    }

    public function test_validate_user_pass_returns_false_for_invalid_credentials(): void
    {
        $validator = new ArrayCredentialValidator([
            'alice' => ['secret' => new WebDavPrincipal('42', 'Alice')],
        ]);
        $backend = new BasicAuthBackend($validator);

        $result = $this->invokeValidateUserPass($backend, 'alice', 'wrong');

        $this->assertFalse($result);
        $this->assertSame([['username' => 'alice', 'password' => 'wrong']], $validator->calls);
    }

    public function test_principal_remains_null_after_failed_authentication(): void
    {
        $backend = new BasicAuthBackend(new ArrayCredentialValidator());

        $this->invokeValidateUserPass($backend, 'alice', 'wrong');

        $this->assertNull($backend->getPrincipal());
    }

    private function invokeValidateUserPass(BasicAuthBackend $backend, string $username, string $password): bool
    {
        $method = new ReflectionMethod($backend, 'validateUserPass');

        return $method->invoke($backend, $username, $password);
    }
}
