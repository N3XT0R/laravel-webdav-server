<?php

declare(strict_types=1);

namespace N3XT0R\LaravelWebdavServer\Tests\Unit\Auth;

use Illuminate\Hashing\BcryptHasher;
use N3XT0R\LaravelWebdavServer\Auth\Validators\DatabaseCredentialValidator;
use N3XT0R\LaravelWebdavServer\DTO\Auth\WebDavAccountRecordDto;
use N3XT0R\LaravelWebdavServer\Tests\Fixtures\Repositories\InMemoryWebDavAccountRepository;
use N3XT0R\LaravelWebdavServer\ValueObjects\WebDavPrincipal;
use PHPUnit\Framework\TestCase;
use Workbench\App\Models\User;

final class DatabaseCredentialValidatorTest extends TestCase
{
    public function test_it_returns_null_when_account_is_not_found(): void
    {
        $repository = new InMemoryWebDavAccountRepository();
        $validator = new DatabaseCredentialValidator($repository, new BcryptHasher);

        $this->assertNull($validator->validate('unknown', 'password'));
        $this->assertSame(['unknown'], $repository->lookups);
    }

    public function test_it_returns_null_when_password_does_not_match(): void
    {
        $repository = new InMemoryWebDavAccountRepository([
            'alice' => new WebDavAccountRecordDto(
                '42',
                'Alice',
                (new BcryptHasher)->make('secret'),
            ),
        ]);

        $validator = new DatabaseCredentialValidator($repository, new BcryptHasher);

        $this->assertNull($validator->validate('alice', 'wrong'));
    }

    public function test_it_returns_principal_for_valid_credentials(): void
    {
        $repository = new InMemoryWebDavAccountRepository([
            'alice' => new WebDavAccountRecordDto(
                '42',
                'Alice',
                (new BcryptHasher)->make('secret'),
            ),
        ]);

        $validator = new DatabaseCredentialValidator($repository, new BcryptHasher);
        $result = $validator->validate('alice', 'secret');

        $this->assertInstanceOf(WebDavPrincipal::class, $result);
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

        $repository = new InMemoryWebDavAccountRepository([
            'alice' => new WebDavAccountRecordDto(
                '42',
                'Alice',
                (new BcryptHasher)->make('secret'),
                $user,
            ),
        ]);

        $validator = new DatabaseCredentialValidator($repository, new BcryptHasher);
        $result = $validator->validate('alice', 'secret');

        $this->assertSame($user, $result->user);
    }

    public function test_it_returns_null_user_in_principal_when_account_has_no_user(): void
    {
        $repository = new InMemoryWebDavAccountRepository([
            'alice' => new WebDavAccountRecordDto(
                '42',
                'Alice',
                (new BcryptHasher)->make('secret'),
            ),
        ]);

        $validator = new DatabaseCredentialValidator($repository, new BcryptHasher);
        $result = $validator->validate('alice', 'secret');

        $this->assertNull($result->user);
    }
}
