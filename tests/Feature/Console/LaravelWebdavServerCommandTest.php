<?php

declare(strict_types=1);

namespace N3XT0R\LaravelWebdavServer\Tests\Feature\Console;

use Illuminate\Contracts\Config\Repository;
use Illuminate\Support\Facades\Hash;
use N3XT0R\LaravelWebdavServer\Models\WebDavAccountModel;
use N3XT0R\LaravelWebdavServer\Tests\DatabaseTestCase;

final class LaravelWebdavServerCommandTest extends DatabaseTestCase
{
    public function test_root_command_lists_available_account_commands(): void
    {
        $this->artisan('laravel-webdav-server')
            ->expectsOutputToContain('Laravel WebDAV Server artisan commands')
            ->expectsOutputToContain('laravel-webdav-server:account:create')
            ->expectsOutputToContain('laravel-webdav-server:account:update')
            ->assertExitCode(0);
    }

    public function test_create_command_creates_a_hashed_webdav_account_record(): void
    {
        $this->artisan('laravel-webdav-server:account:create', [
            'username' => 'console-user',
            'secret' => 'secret-value',
            '--display-name' => 'Console User',
            '--user-id' => '42',
        ])
            ->expectsOutputToContain("Created WebDAV account 'console-user'.")
            ->assertExitCode(0);

        $account = WebDavAccountModel::query()
            ->where('username', 'console-user')
            ->firstOrFail();

        $this->assertSame('Console User', $account->display_name);
        $this->assertSame(42, $account->user_id);
        $this->assertTrue($account->enabled);
        $this->assertTrue(Hash::check('secret-value', (string) $account->password_encrypted));
    }

    public function test_list_command_renders_existing_accounts(): void
    {
        WebDavAccountModel::factory()->withUserName('alpha')->create([
            'display_name' => 'Alpha User',
        ]);
        WebDavAccountModel::factory()->withUserName('beta')->create([
            'display_name' => 'Beta User',
            'enabled' => false,
        ]);

        $this->artisan('laravel-webdav-server:account:list')
            ->expectsOutputToContain('alpha')
            ->expectsOutputToContain('beta')
            ->assertExitCode(0);
    }

    public function test_show_command_renders_the_requested_account_details(): void
    {
        WebDavAccountModel::factory()->withUserName('detail-user')->create([
            'display_name' => 'Detail User',
            'user_id' => 7,
        ]);

        $this->artisan('laravel-webdav-server:account:show', [
            'username' => 'detail-user',
        ])
            ->expectsOutputToContain('detail-user')
            ->expectsOutputToContain('Detail User')
            ->expectsOutputToContain('7')
            ->assertExitCode(0);
    }

    public function test_update_command_updates_existing_accounts(): void
    {
        WebDavAccountModel::factory()->withUserName('before-update')->create([
            'display_name' => 'Before Update',
            'enabled' => true,
            'user_id' => 5,
        ]);

        $this->artisan('laravel-webdav-server:account:update', [
            'username' => 'before-update',
            '--new-username' => 'after-update',
            '--secret' => 'changed-password',
            '--display-name' => 'After Update',
            '--clear-user-id' => true,
            '--disable' => true,
        ])
            ->expectsOutputToContain("Updated WebDAV account 'after-update'.")
            ->assertExitCode(0);

        $account = WebDavAccountModel::query()
            ->where('username', 'after-update')
            ->firstOrFail();

        $this->assertSame('After Update', $account->display_name);
        $this->assertFalse($account->enabled);
        $this->assertNull($account->user_id);
        $this->assertTrue(Hash::check('changed-password', (string) $account->password_encrypted));
    }

    public function test_update_command_rejects_conflicting_options(): void
    {
        WebDavAccountModel::factory()->withUserName('conflict-user')->create();

        $this->artisan('laravel-webdav-server:account:update', [
            'username' => 'conflict-user',
            '--enable' => true,
            '--disable' => true,
        ])
            ->expectsOutputToContain('Use either --enable or --disable, not both.')
            ->assertExitCode(1);
    }

    // --- create: additional cases ---

    public function test_create_command_creates_disabled_account_when_disabled_flag_is_set(): void
    {
        $this->artisan('laravel-webdav-server:account:create', [
            'username' => 'disabled-user',
            'secret' => 'secret',
            '--disabled' => true,
        ])->assertExitCode(0);

        $account = WebDavAccountModel::query()->where('username', 'disabled-user')->firstOrFail();

        $this->assertFalse($account->enabled);
    }

    public function test_create_command_falls_back_to_username_as_display_name_when_not_given(): void
    {
        $this->artisan('laravel-webdav-server:account:create', [
            'username' => 'no-display',
            'secret' => 'secret',
        ])->assertExitCode(0);

        $account = WebDavAccountModel::query()->where('username', 'no-display')->firstOrFail();

        $this->assertSame('no-display', $account->display_name);
        $this->assertNull($account->user_id);
    }

    public function test_create_command_returns_failure_for_duplicate_username(): void
    {
        WebDavAccountModel::factory()->withUserName('taken')->create();

        $this->artisan('laravel-webdav-server:account:create', [
            'username' => 'taken',
            'secret' => 'secret',
        ])
            ->expectsOutputToContain("A WebDAV account with username 'taken' already exists.")
            ->assertExitCode(1);
    }

    // --- list: additional cases ---

    public function test_list_command_warns_when_no_accounts_exist(): void
    {
        $this->artisan('laravel-webdav-server:account:list')
            ->expectsOutputToContain('No WebDAV accounts found.')
            ->assertExitCode(0);
    }

    public function test_list_command_renders_dash_for_unconfigured_optional_columns(): void
    {
        $this->app->make(Repository::class)->set([
            'webdav-server.auth.enabled_column' => '',
            'webdav-server.auth.display_name_column' => '',
        ]);

        WebDavAccountModel::factory()->withUserName('col-test')->create();

        $this->artisan('laravel-webdav-server:account:list')
            ->expectsOutputToContain('col-test')
            ->assertExitCode(0);
    }

    // --- show: additional cases ---

    public function test_show_command_returns_failure_when_account_not_found(): void
    {
        $this->artisan('laravel-webdav-server:account:show', ['username' => 'ghost'])
            ->expectsOutputToContain("No WebDAV account found for username 'ghost'.")
            ->assertExitCode(1);
    }

    // --- update: additional cases ---

    public function test_update_command_returns_failure_when_account_not_found(): void
    {
        $this->artisan('laravel-webdav-server:account:update', [
            'username' => 'nobody',
            '--new-username' => 'still-nobody',
        ])
            ->expectsOutputToContain("No WebDAV account found for username 'nobody'.")
            ->assertExitCode(1);
    }

    public function test_update_command_rejects_user_id_and_clear_user_id_together(): void
    {
        WebDavAccountModel::factory()->withUserName('uid-conflict')->create();

        $this->artisan('laravel-webdav-server:account:update', [
            'username' => 'uid-conflict',
            '--user-id' => '5',
            '--clear-user-id' => true,
        ])
            ->expectsOutputToContain('Use either --user-id or --clear-user-id, not both.')
            ->assertExitCode(1);
    }

    public function test_update_command_rejects_display_name_and_clear_display_name_together(): void
    {
        WebDavAccountModel::factory()->withUserName('dn-conflict')->create();

        $this->artisan('laravel-webdav-server:account:update', [
            'username' => 'dn-conflict',
            '--display-name' => 'New Name',
            '--clear-display-name' => true,
        ])
            ->expectsOutputToContain('Use either --display-name or --clear-display-name, not both.')
            ->assertExitCode(1);
    }

    public function test_update_command_returns_failure_for_duplicate_new_username(): void
    {
        WebDavAccountModel::factory()->withUserName('existing')->create();
        WebDavAccountModel::factory()->withUserName('original')->create();

        $this->artisan('laravel-webdav-server:account:update', [
            'username' => 'original',
            '--new-username' => 'existing',
        ])
            ->expectsOutputToContain("A WebDAV account with username 'existing' already exists.")
            ->assertExitCode(1);
    }

    public function test_update_command_returns_failure_when_no_changes_requested(): void
    {
        WebDavAccountModel::factory()->withUserName('no-change')->create();

        $this->artisan('laravel-webdav-server:account:update', ['username' => 'no-change'])
            ->expectsOutputToContain('No changes requested.')
            ->assertExitCode(1);
    }

    public function test_update_command_enables_a_disabled_account(): void
    {
        WebDavAccountModel::factory()->withUserName('was-disabled')->create(['enabled' => false]);

        $this->artisan('laravel-webdav-server:account:update', [
            'username' => 'was-disabled',
            '--enable' => true,
        ])->assertExitCode(0);

        $this->assertTrue(WebDavAccountModel::query()->where('username', 'was-disabled')->firstOrFail()->enabled);
    }

    public function test_update_command_clears_display_name(): void
    {
        WebDavAccountModel::factory()->withUserName('clear-dn')->create(['display_name' => 'To Be Cleared']);

        $this->artisan('laravel-webdav-server:account:update', [
            'username' => 'clear-dn',
            '--clear-display-name' => true,
        ])->assertExitCode(0);

        $this->assertNull(WebDavAccountModel::query()->where('username', 'clear-dn')->firstOrFail()->display_name);
    }
}
