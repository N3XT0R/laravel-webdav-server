<?php

declare(strict_types=1);

namespace N3XT0R\LaravelWebdavServer\Tests\Unit\DTO\Auth;

use N3XT0R\LaravelWebdavServer\DTO\Auth\AccountRecordDto;
use PHPUnit\Framework\TestCase;
use Workbench\App\Models\User;

final class AccountRecordDtoTest extends TestCase
{
    public function test_it_returns_principal_id(): void
    {
        $dto = new AccountRecordDto(
            principalId: 'users/test',
            displayName: 'Test User',
            passwordHash: 'hashed-password',
        );

        self::assertSame('users/test', $dto->getPrincipalId());
    }

    public function test_it_returns_display_name(): void
    {
        $dto = new AccountRecordDto(
            principalId: 'users/test',
            displayName: 'Test User',
            passwordHash: 'hashed-password',
        );

        self::assertSame('Test User', $dto->getDisplayName());
    }

    public function test_it_returns_password_hash(): void
    {
        $dto = new AccountRecordDto(
            principalId: 'users/test',
            displayName: 'Test User',
            passwordHash: 'hashed-password',
        );

        self::assertSame('hashed-password', $dto->getPasswordHash());
    }

    public function test_it_returns_user(): void
    {
        $user = new User;
        $user->setRawAttributes([
            'name' => 'Test User',
            'email' => 'test@example.com',
            'password' => 'secret',
        ], true);

        $dto = new AccountRecordDto(
            principalId: 'users/test',
            displayName: 'Test User',
            passwordHash: 'hashed-password',
            user: $user,
        );

        self::assertSame($user, $dto->getUser());
    }

    public function test_it_returns_null_user_when_no_user_was_provided(): void
    {
        $dto = new AccountRecordDto(
            principalId: 'users/test',
            displayName: 'Test User',
            passwordHash: 'hashed-password',
        );

        self::assertNull($dto->getUser());
    }
}
