<?php

declare(strict_types=1);

namespace N3XT0R\LaravelWebdavServer\Tests\Feature\Console;

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
}
