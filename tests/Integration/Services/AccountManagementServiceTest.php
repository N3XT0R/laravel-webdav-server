<?php

declare(strict_types=1);

namespace N3XT0R\LaravelWebdavServer\Tests\Integration\Services;

use Illuminate\Contracts\Config\Repository;
use Illuminate\Support\Facades\Hash;
use N3XT0R\LaravelWebdavServer\DTO\Management\AccountUpdateDto;
use N3XT0R\LaravelWebdavServer\Models\WebDavAccountModel;
use N3XT0R\LaravelWebdavServer\Exception\Auth\DuplicateUsernameException;
use N3XT0R\LaravelWebdavServer\Repositories\EloquentAccountRepository;
use N3XT0R\LaravelWebdavServer\Services\AccountCreateService;
use N3XT0R\LaravelWebdavServer\Services\AccountManagementService;
use N3XT0R\LaravelWebdavServer\Services\AccountUpdateService;
use N3XT0R\LaravelWebdavServer\Tests\DatabaseTestCase;

final class AccountManagementServiceTest extends DatabaseTestCase
{
    private function makeService(): AccountManagementService
    {
        $repository = new EloquentAccountRepository($this->app->make(Repository::class));

        return new AccountManagementService(
            $repository,
            new AccountCreateService($repository),
            new AccountUpdateService($repository),
        );
    }

    // --- columnMapping ---

    public function test_column_mapping_returns_configured_column_names(): void
    {
        $mapping = $this->makeService()->columnMapping();

        $this->assertSame(WebDavAccountModel::class, $mapping->modelClass);
        $this->assertSame('username', $mapping->usernameColumn);
        $this->assertSame('password_encrypted', $mapping->passwordColumn);
        $this->assertSame('enabled', $mapping->enabledColumn);
        $this->assertSame('user_id', $mapping->userIdColumn);
        $this->assertSame('display_name', $mapping->displayNameColumn);
    }

    // --- findByUsername ---

    public function test_find_by_username_returns_null_when_no_account_exists(): void
    {
        $this->assertNull($this->makeService()->findByUsername('ghost'));
    }

    public function test_find_by_username_returns_model_for_existing_account(): void
    {
        WebDavAccountModel::factory()->create(['username' => 'alice']);

        $result = $this->makeService()->findByUsername('alice');

        $this->assertNotNull($result);
        $this->assertSame('alice', $result->getAttribute('username'));
    }

    public function test_find_by_username_returns_disabled_accounts(): void
    {
        WebDavAccountModel::factory()->create(['username' => 'inactive', 'enabled' => false]);

        $result = $this->makeService()->findByUsername('inactive');

        $this->assertNotNull($result);
        $this->assertFalse((bool) $result->getAttribute('enabled'));
    }

    // --- all ---

    public function test_all_returns_empty_collection_when_no_accounts_exist(): void
    {
        $this->assertCount(0, $this->makeService()->all());
    }

    public function test_all_returns_all_accounts_ordered_by_username(): void
    {
        WebDavAccountModel::factory()->create(['username' => 'zara']);
        WebDavAccountModel::factory()->create(['username' => 'alice']);
        WebDavAccountModel::factory()->create(['username' => 'mike']);

        $accounts = $this->makeService()->all();

        $this->assertCount(3, $accounts);
        $this->assertSame('alice', $accounts[0]->getAttribute('username'));
        $this->assertSame('mike', $accounts[1]->getAttribute('username'));
        $this->assertSame('zara', $accounts[2]->getAttribute('username'));
    }

    // --- create ---

    public function test_create_persists_new_account_to_database(): void
    {
        $this->makeService()->create('bob', 'secret');

        $this->assertDatabaseHas('webdav_accounts', ['username' => 'bob']);
    }

    public function test_create_returns_the_persisted_model(): void
    {
        $account = $this->makeService()->create('bob', 'secret');

        $this->assertInstanceOf(WebDavAccountModel::class, $account);
        $this->assertNotNull($account->getAttribute('id'));
    }

    public function test_create_stores_password_as_hash(): void
    {
        $this->makeService()->create('bob', 'plaintext');

        $stored = WebDavAccountModel::query()->where('username', 'bob')->first();

        $this->assertNotSame('plaintext', $stored->getAttribute('password_encrypted'));
        $this->assertTrue(Hash::check('plaintext', $stored->getAttribute('password_encrypted')));
    }

    public function test_create_uses_username_as_display_name_when_display_name_is_null(): void
    {
        $this->makeService()->create('carol', 'secret', null);

        $stored = WebDavAccountModel::query()->where('username', 'carol')->first();

        $this->assertSame('carol', $stored->getAttribute('display_name'));
    }

    public function test_create_uses_username_as_display_name_when_display_name_is_blank(): void
    {
        $this->makeService()->create('carol', 'secret', '   ');

        $stored = WebDavAccountModel::query()->where('username', 'carol')->first();

        $this->assertSame('carol', $stored->getAttribute('display_name'));
    }

    public function test_create_uses_provided_display_name(): void
    {
        $this->makeService()->create('dave', 'secret', 'Dave Smith');

        $stored = WebDavAccountModel::query()->where('username', 'dave')->first();

        $this->assertSame('Dave Smith', $stored->getAttribute('display_name'));
    }

    public function test_create_sets_enabled_flag(): void
    {
        $this->makeService()->create('eve', 'secret', null, null, false);

        $stored = WebDavAccountModel::query()->where('username', 'eve')->first();

        $this->assertFalse((bool) $stored->getAttribute('enabled'));
    }

    public function test_create_sets_user_id_when_provided(): void
    {
        $this->makeService()->create('frank', 'secret', null, 42);

        $stored = WebDavAccountModel::query()->where('username', 'frank')->first();

        $this->assertSame(42, $stored->getAttribute('user_id'));
    }

    public function test_create_leaves_user_id_null_when_not_provided(): void
    {
        $this->makeService()->create('grace', 'secret');

        $stored = WebDavAccountModel::query()->where('username', 'grace')->first();

        $this->assertNull($stored->getAttribute('user_id'));
    }

    public function test_create_throws_when_username_is_already_taken(): void
    {
        $this->expectException(DuplicateUsernameException::class);
        $this->expectExceptionMessage("A WebDAV account with username 'existing' already exists.");

        WebDavAccountModel::factory()->create(['username' => 'existing']);
        $this->makeService()->create('existing', 'secret');
    }

    // --- update ---

    public function test_update_returns_false_when_no_fields_changed(): void
    {
        $account = WebDavAccountModel::factory()->create(['username' => 'alice']);
        $dto = new AccountUpdateDto;

        $changed = $this->makeService()->update($account, $dto);

        $this->assertFalse($changed);
    }

    public function test_update_returns_true_and_persists_username_change(): void
    {
        $account = WebDavAccountModel::factory()->create(['username' => 'alice']);
        $dto = new AccountUpdateDto(newUsername: 'alice-renamed');

        $changed = $this->makeService()->update($account, $dto);

        $this->assertTrue($changed);
        $this->assertDatabaseHas('webdav_accounts', ['username' => 'alice-renamed']);
        $this->assertDatabaseMissing('webdav_accounts', ['username' => 'alice']);
    }

    public function test_update_does_not_count_same_username_as_a_change(): void
    {
        $account = WebDavAccountModel::factory()->create(['username' => 'alice']);
        $dto = new AccountUpdateDto(newUsername: 'alice');

        $changed = $this->makeService()->update($account, $dto);

        $this->assertFalse($changed);
    }

    public function test_update_throws_when_new_username_is_already_taken(): void
    {
        $this->expectException(DuplicateUsernameException::class);
        $this->expectExceptionMessage("A WebDAV account with username 'bob' already exists.");

        WebDavAccountModel::factory()->create(['username' => 'bob']);
        $account = WebDavAccountModel::factory()->create(['username' => 'alice']);
        $dto = new AccountUpdateDto(newUsername: 'bob');

        $this->makeService()->update($account, $dto);
    }

    public function test_update_replaces_password_with_new_hash(): void
    {
        $account = WebDavAccountModel::factory()->create(['username' => 'alice']);
        $dto = new AccountUpdateDto(password: 'newpassword');

        $this->makeService()->update($account, $dto);

        $stored = WebDavAccountModel::query()->where('username', 'alice')->first();

        $this->assertTrue(Hash::check('newpassword', $stored->getAttribute('password_encrypted')));
    }

    public function test_update_does_not_change_password_when_empty_string_is_given(): void
    {
        $account = WebDavAccountModel::factory()->create([
            'username' => 'alice',
            'password_encrypted' => Hash::make('original'),
        ]);
        $originalHash = $account->getAttribute('password_encrypted');
        $dto = new AccountUpdateDto(password: '');

        $this->makeService()->update($account, $dto);

        $stored = WebDavAccountModel::query()->where('username', 'alice')->first();

        $this->assertSame($originalHash, $stored->getAttribute('password_encrypted'));
    }

    public function test_update_sets_display_name(): void
    {
        $account = WebDavAccountModel::factory()->create(['username' => 'alice', 'display_name' => 'Old Name']);
        $dto = new AccountUpdateDto(displayName: 'New Name');

        $this->makeService()->update($account, $dto);

        $stored = WebDavAccountModel::query()->where('username', 'alice')->first();

        $this->assertSame('New Name', $stored->getAttribute('display_name'));
    }

    public function test_update_clears_display_name_when_clear_flag_is_set(): void
    {
        $account = WebDavAccountModel::factory()->create(['username' => 'alice', 'display_name' => 'Has Name']);
        $dto = new AccountUpdateDto(clearDisplayName: true);

        $this->makeService()->update($account, $dto);

        $stored = WebDavAccountModel::query()->where('username', 'alice')->first();

        $this->assertNull($stored->getAttribute('display_name'));
    }

    public function test_update_clear_display_name_takes_precedence_over_display_name(): void
    {
        $account = WebDavAccountModel::factory()->create(['username' => 'alice', 'display_name' => 'Old Name']);
        $dto = new AccountUpdateDto(displayName: 'Should Be Ignored', clearDisplayName: true);

        $this->makeService()->update($account, $dto);

        $stored = WebDavAccountModel::query()->where('username', 'alice')->first();

        $this->assertNull($stored->getAttribute('display_name'));
    }

    public function test_update_sets_user_id(): void
    {
        $account = WebDavAccountModel::factory()->create(['username' => 'alice', 'user_id' => null]);
        $dto = new AccountUpdateDto(userId: 99);

        $this->makeService()->update($account, $dto);

        $stored = WebDavAccountModel::query()->where('username', 'alice')->first();

        $this->assertSame(99, $stored->getAttribute('user_id'));
    }

    public function test_update_clears_user_id_when_clear_flag_is_set(): void
    {
        $account = WebDavAccountModel::factory()->create(['username' => 'alice', 'user_id' => 5]);
        $dto = new AccountUpdateDto(clearUserId: true);

        $this->makeService()->update($account, $dto);

        $stored = WebDavAccountModel::query()->where('username', 'alice')->first();

        $this->assertNull($stored->getAttribute('user_id'));
    }

    public function test_update_sets_enabled_flag(): void
    {
        $account = WebDavAccountModel::factory()->create(['username' => 'alice', 'enabled' => true]);
        $dto = new AccountUpdateDto(enabled: false);

        $this->makeService()->update($account, $dto);

        $stored = WebDavAccountModel::query()->where('username', 'alice')->first();

        $this->assertFalse((bool) $stored->getAttribute('enabled'));
    }

    public function test_update_does_not_persist_when_enabled_is_null(): void
    {
        $account = WebDavAccountModel::factory()->create(['username' => 'alice', 'enabled' => true]);
        $originalUpdatedAt = $account->getAttribute('updated_at');
        $dto = new AccountUpdateDto(enabled: null);

        $changed = $this->makeService()->update($account, $dto);

        $this->assertFalse($changed);
        $stored = WebDavAccountModel::query()->where('username', 'alice')->first();
        $this->assertSame($originalUpdatedAt?->toDateTimeString(), $stored->getAttribute('updated_at')?->toDateTimeString());
    }
}
