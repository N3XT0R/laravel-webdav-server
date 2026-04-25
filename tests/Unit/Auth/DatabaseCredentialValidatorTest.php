<?php

declare(strict_types=1);

namespace N3XT0R\LaravelWebdavServer\Tests\Unit\Auth;

use Illuminate\Hashing\BcryptHasher;
use N3XT0R\LaravelWebdavServer\Auth\Validators\DatabaseCredentialValidator;
use N3XT0R\LaravelWebdavServer\DTO\Auth\AccountRecordDto;
use N3XT0R\LaravelWebdavServer\Exception\Auth\InvalidCredentialsException;
use N3XT0R\LaravelWebdavServer\Logging\WebDavLoggingService;
use N3XT0R\LaravelWebdavServer\Tests\Fixtures\Logging\RecordingLogger;
use N3XT0R\LaravelWebdavServer\Tests\Fixtures\Repositories\InMemoryAccountRepository;
use N3XT0R\LaravelWebdavServer\ValueObjects\WebDavPrincipalValueObject;
use PHPUnit\Framework\TestCase;
use Workbench\App\Models\User;

final class DatabaseCredentialValidatorTest extends TestCase
{
    public function test_it_throws_when_account_is_not_found(): void
    {
        $this->expectException(InvalidCredentialsException::class);

        $repository = new InMemoryAccountRepository;
        $logger = new RecordingLogger;
        $validator = new DatabaseCredentialValidator(
            $repository,
            new BcryptHasher,
            new WebDavLoggingService($logger, 'stderr', 'debug'),
        );

        try {
            $validator->validate('unknown', 'password');
        } finally {
            $this->assertSame(['unknown'], $repository->lookups);
            $this->assertSame('WebDAV account lookup failed during credential validation.', $logger->records[0]['message']);
        }
    }

    public function test_it_throws_when_password_does_not_match(): void
    {
        $this->expectException(InvalidCredentialsException::class);

        $repository = new InMemoryAccountRepository([
            'alice' => new AccountRecordDto(
                '42',
                'Alice',
                (new BcryptHasher)->make('secret'),
            ),
        ]);

        $validator = new DatabaseCredentialValidator(
            $repository,
            new BcryptHasher,
            new WebDavLoggingService(new RecordingLogger, 'stderr', 'debug'),
        );

        $validator->validate('alice', 'wrong');
    }

    public function test_it_returns_principal_for_valid_credentials(): void
    {
        $repository = new InMemoryAccountRepository([
            'alice' => new AccountRecordDto(
                '42',
                'Alice',
                (new BcryptHasher)->make('secret'),
            ),
        ]);

        $validator = new DatabaseCredentialValidator(
            $repository,
            new BcryptHasher,
            new WebDavLoggingService(new RecordingLogger, 'stderr', 'debug'),
        );
        $result = $validator->validate('alice', 'secret');

        $this->assertInstanceOf(WebDavPrincipalValueObject::class, $result);
        $this->assertSame('42', $result->id);
        $this->assertSame('Alice', $result->displayName);
    }

    public function test_it_passes_the_account_user_to_the_principal(): void
    {
        $user = new User;
        $user->setRawAttributes([
            'name' => 'Alice',
            'email' => 'alice@example.test',
            'password' => 'secret',
            $user->getKeyName() => 42,
        ], true);

        $repository = new InMemoryAccountRepository([
            'alice' => new AccountRecordDto(
                '42',
                'Alice',
                (new BcryptHasher)->make('secret'),
                $user,
            ),
        ]);

        $validator = new DatabaseCredentialValidator(
            $repository,
            new BcryptHasher,
            new WebDavLoggingService(new RecordingLogger, 'stderr', 'debug'),
        );
        $result = $validator->validate('alice', 'secret');

        $this->assertSame($user, $result->user);
    }

    public function test_it_returns_null_user_in_principal_when_account_has_no_user(): void
    {
        $repository = new InMemoryAccountRepository([
            'alice' => new AccountRecordDto(
                '42',
                'Alice',
                (new BcryptHasher)->make('secret'),
            ),
        ]);

        $validator = new DatabaseCredentialValidator(
            $repository,
            new BcryptHasher,
            new WebDavLoggingService(new RecordingLogger, 'stderr', 'debug'),
        );
        $result = $validator->validate('alice', 'secret');

        $this->assertNull($result->user);
    }
}
