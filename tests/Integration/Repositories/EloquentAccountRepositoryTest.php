<?php

declare(strict_types=1);

namespace N3XT0R\LaravelWebdavServer\Tests\Integration\Repositories;

use Illuminate\Contracts\Config\Repository;
use N3XT0R\LaravelWebdavServer\Contracts\Auth\AccountInterface;
use N3XT0R\LaravelWebdavServer\Exception\Auth\AccountDisabledException;
use N3XT0R\LaravelWebdavServer\Exception\Auth\AccountNotFoundException;
use N3XT0R\LaravelWebdavServer\Exception\Auth\InvalidAccountConfigurationException;
use N3XT0R\LaravelWebdavServer\Exception\Auth\InvalidAccountRecordException;
use N3XT0R\LaravelWebdavServer\Models\WebDavAccountModel;
use N3XT0R\LaravelWebdavServer\Repositories\EloquentAccountRepository;
use N3XT0R\LaravelWebdavServer\Tests\DatabaseTestCase;
use N3XT0R\LaravelWebdavServer\Tests\Fixtures\Models\InvalidDisplayNameAccountModel;
use Workbench\App\Models\User;

final class EloquentAccountRepositoryTest extends DatabaseTestCase
{
    protected function defineEnvironment($app): void
    {
        parent::defineEnvironment($app);

        // Use 'id' as the principal-id column so accounts without a linked user work.
        $app->make(Repository::class)->set('webdav-server.auth.user_id_column', 'id');
    }

    private function makeRepository(): EloquentAccountRepository
    {
        return $this->app->make(EloquentAccountRepository::class);
    }

    public function test_it_throws_when_username_is_not_found(): void
    {
        $this->expectException(AccountNotFoundException::class);

        $this->makeRepository()->findEnabledByUsername('nonexistent');
    }

    public function test_it_throws_for_a_disabled_account(): void
    {
        $this->expectException(AccountDisabledException::class);

        WebDavAccountModel::factory()->create([
            'username' => 'disabled-user',
            'enabled' => false,
        ]);

        $this->makeRepository()->findEnabledByUsername('disabled-user');
    }

    public function test_it_returns_account_interface_for_an_enabled_account(): void
    {
        $account = WebDavAccountModel::factory()->create([
            'username' => 'alice',
            'display_name' => 'Alice',
            'enabled' => true,
        ]);

        $result = $this->makeRepository()->findEnabledByUsername('alice');

        $this->assertInstanceOf(AccountInterface::class, $result);
        $this->assertSame((string) $account->id, $result->getPrincipalId());
        $this->assertSame('Alice', $result->getDisplayName());
    }

    public function test_it_returns_correct_password_hash(): void
    {
        WebDavAccountModel::factory()->create([
            'username' => 'bob',
            'password_encrypted' => '$2y$10$fixedhashfortest',
            'enabled' => true,
        ]);

        $result = $this->makeRepository()->findEnabledByUsername('bob');

        $this->assertSame('$2y$10$fixedhashfortest', $result->getPasswordHash());
    }

    public function test_it_returns_null_user_when_no_user_is_linked(): void
    {
        WebDavAccountModel::factory()->create([
            'username' => 'nouser',
            'user_id' => null,
            'enabled' => true,
        ]);

        $result = $this->makeRepository()->findEnabledByUsername('nouser');

        $this->assertNull($result->getUser());
    }

    public function test_it_returns_linked_user_when_account_has_user_id(): void
    {
        $user = User::factory()->create();

        WebDavAccountModel::factory()->create([
            'username' => 'linked',
            'user_id' => $user->id,
            'enabled' => true,
        ]);

        $result = $this->makeRepository()->findEnabledByUsername('linked');

        $this->assertNotNull($result->getUser());
        $this->assertSame($user->id, $result->getUser()->getAuthIdentifier());
    }

    public function test_it_throws_when_account_model_is_not_configured(): void
    {
        $this->expectException(InvalidAccountConfigurationException::class);

        $config = $this->app->make(Repository::class);
        $config->set('webdav-server.auth.account_model', null);

        $this->makeRepository()->findEnabledByUsername('alice');
    }

    public function test_it_throws_when_account_model_is_not_an_eloquent_model(): void
    {
        $this->expectException(InvalidAccountConfigurationException::class);

        $config = $this->app->make(Repository::class);
        $config->set('webdav-server.auth.account_model', \stdClass::class);

        $this->makeRepository()->findEnabledByUsername('alice');
    }

    public function test_it_throws_when_the_account_record_contains_non_scalar_attributes(): void
    {
        $this->expectException(InvalidAccountRecordException::class);

        $config = $this->app->make(Repository::class);
        $config->set('webdav-server.auth.account_model', InvalidDisplayNameAccountModel::class);

        InvalidDisplayNameAccountModel::query()->create([
            'username' => 'invalid-display-name',
            'password_encrypted' => '$2y$10$fixedhashfortest',
            'enabled' => true,
            'display_name' => 'ignored-by-fixture',
        ]);

        $this->makeRepository()->findEnabledByUsername('invalid-display-name');
    }
}
