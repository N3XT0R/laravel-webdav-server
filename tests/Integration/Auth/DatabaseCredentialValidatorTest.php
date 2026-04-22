<?php

declare(strict_types=1);

namespace N3XT0R\LaravelWebdavServer\Tests\Integration\Auth;

use Illuminate\Contracts\Auth\Authenticatable;
use Illuminate\Contracts\Hashing\Hasher;
use N3XT0R\LaravelWebdavServer\Auth\Validators\DatabaseCredentialValidator;
use N3XT0R\LaravelWebdavServer\Contracts\Repositories\WebDavAccountRepositoryInterface;
use N3XT0R\LaravelWebdavServer\DTO\Auth\WebDavAccountRecordDto;
use N3XT0R\LaravelWebdavServer\Tests\TestCase;
use N3XT0R\LaravelWebdavServer\ValueObjects\WebDavPrincipal;

final class DatabaseCredentialValidatorTest extends TestCase
{
    public function test_it_returns_null_when_account_is_not_found(): void
    {
        $repository = $this->createMock(WebDavAccountRepositoryInterface::class);
        $repository->method('findEnabledByUsername')->with('unknown')->willReturn(null);

        $hasher = $this->createMock(Hasher::class);

        $validator = new DatabaseCredentialValidator($repository, $hasher);

        $this->assertNull($validator->validate('unknown', 'password'));
    }

    public function test_it_returns_null_when_password_does_not_match(): void
    {
        $account = new WebDavAccountRecordDto('42', 'Alice', '$2y$hashed');

        $repository = $this->createMock(WebDavAccountRepositoryInterface::class);
        $repository->method('findEnabledByUsername')->with('alice')->willReturn($account);

        $hasher = $this->createMock(Hasher::class);
        $hasher->method('check')->with('wrong', '$2y$hashed')->willReturn(false);

        $validator = new DatabaseCredentialValidator($repository, $hasher);

        $this->assertNull($validator->validate('alice', 'wrong'));
    }

    public function test_it_returns_principal_for_valid_credentials(): void
    {
        $account = new WebDavAccountRecordDto('42', 'Alice', '$2y$hashed');

        $repository = $this->createMock(WebDavAccountRepositoryInterface::class);
        $repository->method('findEnabledByUsername')->with('alice')->willReturn($account);

        $hasher = $this->createMock(Hasher::class);
        $hasher->method('check')->with('secret', '$2y$hashed')->willReturn(true);

        $validator = new DatabaseCredentialValidator($repository, $hasher);
        $result = $validator->validate('alice', 'secret');

        $this->assertInstanceOf(WebDavPrincipal::class, $result);
        $this->assertSame('42', $result->id);
        $this->assertSame('Alice', $result->displayName);
    }

    public function test_it_passes_the_account_user_to_the_principal(): void
    {
        $user = $this->createMock(Authenticatable::class);
        $account = new WebDavAccountRecordDto('42', 'Alice', '$2y$hashed', $user);

        $repository = $this->createMock(WebDavAccountRepositoryInterface::class);
        $repository->method('findEnabledByUsername')->willReturn($account);

        $hasher = $this->createMock(Hasher::class);
        $hasher->method('check')->willReturn(true);

        $validator = new DatabaseCredentialValidator($repository, $hasher);
        $result = $validator->validate('alice', 'secret');

        $this->assertSame($user, $result->user);
    }

    public function test_it_returns_null_user_in_principal_when_account_has_no_user(): void
    {
        $account = new WebDavAccountRecordDto('42', 'Alice', '$2y$hashed');

        $repository = $this->createMock(WebDavAccountRepositoryInterface::class);
        $repository->method('findEnabledByUsername')->willReturn($account);

        $hasher = $this->createMock(Hasher::class);
        $hasher->method('check')->willReturn(true);

        $validator = new DatabaseCredentialValidator($repository, $hasher);
        $result = $validator->validate('alice', 'secret');

        $this->assertNull($result->user);
    }
}
