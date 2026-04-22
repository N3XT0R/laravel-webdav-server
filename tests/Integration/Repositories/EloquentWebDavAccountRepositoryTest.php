<?php

declare(strict_types=1);

namespace N3XT0R\LaravelWebdavServer\Tests\Integration\Repositories;

use Illuminate\Contracts\Config\Repository;
use N3XT0R\LaravelWebdavServer\Contracts\Auth\WebDavAccountInterface;
use N3XT0R\LaravelWebdavServer\Models\WebDavAccount;
use N3XT0R\LaravelWebdavServer\Repositories\EloquentWebDavAccountRepository;
use N3XT0R\LaravelWebdavServer\Tests\DatabaseTestCase;
use RuntimeException;
use Workbench\App\Models\User;

final class EloquentWebDavAccountRepositoryTest extends DatabaseTestCase
{
    protected function defineEnvironment($app): void
    {
        parent::defineEnvironment($app);

        // Use 'id' as the principal-id column so accounts without a linked user work.
        $app->make(Repository::class)->set('webdav-server.auth.user_id_column', 'id');
    }

    private function makeRepository(): EloquentWebDavAccountRepository
    {
        return new EloquentWebDavAccountRepository($this->app->make(Repository::class));
    }

    public function test_it_returns_null_when_username_is_not_found(): void
    {
        $result = $this->makeRepository()->findEnabledByUsername('nonexistent');

        $this->assertNull($result);
    }

    public function test_it_returns_null_for_a_disabled_account(): void
    {
        WebDavAccount::factory()->create([
            'username' => 'disabled-user',
            'enabled' => false,
        ]);

        $result = $this->makeRepository()->findEnabledByUsername('disabled-user');

        $this->assertNull($result);
    }

    public function test_it_returns_account_interface_for_an_enabled_account(): void
    {
        $account = WebDavAccount::factory()->create([
            'username' => 'alice',
            'display_name' => 'Alice',
            'enabled' => true,
        ]);

        $result = $this->makeRepository()->findEnabledByUsername('alice');

        $this->assertInstanceOf(WebDavAccountInterface::class, $result);
        $this->assertSame((string) $account->id, $result->getPrincipalId());
        $this->assertSame('Alice', $result->getDisplayName());
    }

    public function test_it_returns_correct_password_hash(): void
    {
        WebDavAccount::factory()->create([
            'username' => 'bob',
            'password_encrypted' => '$2y$10$fixedhashfortest',
            'enabled' => true,
        ]);

        $result = $this->makeRepository()->findEnabledByUsername('bob');

        $this->assertSame('$2y$10$fixedhashfortest', $result->getPasswordHash());
    }

    public function test_it_returns_null_user_when_no_user_is_linked(): void
    {
        WebDavAccount::factory()->create([
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

        WebDavAccount::factory()->create([
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
        $this->expectException(RuntimeException::class);

        $config = $this->app->make(Repository::class);
        $config->set('webdav-server.auth.account_model', null);

        $this->makeRepository()->findEnabledByUsername('alice');
    }

    public function test_it_throws_when_account_model_is_not_an_eloquent_model(): void
    {
        $this->expectException(RuntimeException::class);

        $config = $this->app->make(Repository::class);
        $config->set('webdav-server.auth.account_model', \stdClass::class);

        $this->makeRepository()->findEnabledByUsername('alice');
    }
}
