<?php

declare(strict_types=1);

namespace N3XT0R\LaravelWebdavServer\Tests\Unit\Policies;

use Illuminate\Contracts\Auth\Authenticatable;
use Illuminate\Contracts\Config\Repository;
use N3XT0R\LaravelWebdavServer\DTO\Auth\PathResourceDto;
use N3XT0R\LaravelWebdavServer\Policies\PathPolicy;
use N3XT0R\LaravelWebdavServer\Tests\TestCase;
use Workbench\App\Models\User;

final class PathPolicyTest extends TestCase
{
    private PathPolicy $policy;

    protected function setUp(): void
    {
        parent::setUp();

        $this->app->make(Repository::class)->set([
            'webdav-server.storage.spaces' => [
                'default' => [
                    'disk' => 'local',
                    'root' => 'webdav',
                    'prefix' => '/',
                ],
                'team' => [
                    'disk' => 's3',
                    'root' => 'shared',
                    'prefix' => 'members',
                ],
            ],
        ]);

        $this->policy = new PathPolicy;
    }

    private function makeUser(string|int $id): Authenticatable
    {
        $user = new User;
        $user->forceFill([
            'name' => 'Test User',
            'email' => sprintf('user-%s@example.test', $id),
            'password' => 'secret',
        ]);
        $user->setAttribute($user->getKeyName(), $id);

        return $user;
    }

    public function test_read_allows_access_to_own_root_path(): void
    {
        $user = $this->makeUser(42);
        $resource = new PathResourceDto('local', 'webdav/42');

        $this->assertTrue($this->policy->read($user, $resource));
    }

    public function test_read_allows_access_to_a_nested_path_under_own_root(): void
    {
        $user = $this->makeUser(42);
        $resource = new PathResourceDto('local', 'webdav/42/documents/report.pdf');

        $this->assertTrue($this->policy->read($user, $resource));
    }

    public function test_read_denies_access_to_another_users_path(): void
    {
        $user = $this->makeUser(42);
        $resource = new PathResourceDto('local', 'webdav/99/private.txt');

        $this->assertFalse($this->policy->read($user, $resource));
    }

    public function test_read_denies_access_to_wrong_disk(): void
    {
        $user = $this->makeUser(42);
        $resource = new PathResourceDto('s3', 'webdav/42/file.txt');

        $this->assertFalse($this->policy->read($user, $resource));
    }

    public function test_read_allows_access_to_a_prefixed_space_for_the_same_user(): void
    {
        $user = $this->makeUser(42);
        $resource = new PathResourceDto('s3', 'shared/members/42/file.txt');

        $this->assertTrue($this->policy->read($user, $resource));
    }

    public function test_read_denies_access_to_storage_root_itself(): void
    {
        $user = $this->makeUser(42);
        $resource = new PathResourceDto('local', 'webdav');

        $this->assertFalse($this->policy->read($user, $resource));
    }

    public function test_read_denies_path_that_starts_with_user_id_but_is_a_different_user(): void
    {
        $user = $this->makeUser(4);
        $resource = new PathResourceDto('local', 'webdav/42/file.txt');

        $this->assertFalse($this->policy->read($user, $resource));
    }

    public function test_write_follows_the_same_rules_as_read(): void
    {
        $user = $this->makeUser(42);

        $this->assertTrue($this->policy->write($user, new PathResourceDto('local', 'webdav/42/file.txt')));
        $this->assertFalse($this->policy->write($user, new PathResourceDto('local', 'webdav/99/file.txt')));
    }

    public function test_delete_follows_the_same_rules_as_read(): void
    {
        $user = $this->makeUser(42);

        $this->assertTrue($this->policy->delete($user, new PathResourceDto('local', 'webdav/42/old.txt')));
        $this->assertFalse($this->policy->delete($user, new PathResourceDto('s3', 'webdav/42/old.txt')));
    }

    public function test_create_directory_follows_the_same_rules_as_read(): void
    {
        $user = $this->makeUser(42);

        $this->assertTrue($this->policy->createDirectory($user, new PathResourceDto('local', 'webdav/42/newdir')));
        $this->assertFalse($this->policy->createDirectory($user, new PathResourceDto('local', 'webdav/99/newdir')));
    }

    public function test_create_file_follows_the_same_rules_as_read(): void
    {
        $user = $this->makeUser(42);

        $this->assertTrue($this->policy->createFile($user, new PathResourceDto('local', 'webdav/42/new.txt')));
        $this->assertFalse($this->policy->createFile($user, new PathResourceDto('local', 'webdav/99/new.txt')));
    }

    public function test_it_strips_leading_and_trailing_slashes_from_path(): void
    {
        $user = $this->makeUser(42);
        $resource = new PathResourceDto('local', '/webdav/42/');

        $this->assertTrue($this->policy->read($user, $resource));
    }

    public function test_read_denies_access_when_spaces_configuration_is_not_an_array(): void
    {
        $user = $this->makeUser(42);
        config()->set('webdav-server.storage.spaces', 'invalid');

        $this->assertFalse($this->policy->read($user, new PathResourceDto('local', 'webdav/42/file.txt')));
    }

    public function test_read_skips_invalid_space_entries_and_still_allows_matching_valid_entries(): void
    {
        $user = $this->makeUser(42);
        config()->set('webdav-server.storage.spaces', [
            'invalid-entry' => 'not-an-array',
            'missing-disk' => [
                'root' => 'broken',
            ],
            'missing-root' => [
                'disk' => 'local',
            ],
            'valid' => [
                'disk' => 'local',
                'root' => 'webdav',
                'prefix' => '/',
            ],
        ]);

        $this->assertTrue($this->policy->read($user, new PathResourceDto('local', 'webdav/42/file.txt')));
    }
}
