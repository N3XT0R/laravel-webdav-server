<?php

declare(strict_types=1);

namespace N3XT0R\LaravelWebdavServer\Tests\Integration\Storage;

use Illuminate\Contracts\Config\Repository;
use N3XT0R\LaravelWebdavServer\Exception\Storage\InvalidSpaceConfigurationException;
use N3XT0R\LaravelWebdavServer\Exception\Storage\SpaceNotConfiguredException;
use N3XT0R\LaravelWebdavServer\Storage\Data\WebDavStorageSpaceValueObject;
use N3XT0R\LaravelWebdavServer\Storage\Resolvers\DefaultSpaceResolver;
use N3XT0R\LaravelWebdavServer\Tests\TestCase;
use N3XT0R\LaravelWebdavServer\ValueObjects\WebDavPrincipalValueObject;

final class DefaultSpaceResolverTest extends TestCase
{
    private function makeResolver(): DefaultSpaceResolver
    {
        return new DefaultSpaceResolver($this->app->make(Repository::class));
    }

    private function setPrincipal(string $id = '42'): WebDavPrincipalValueObject
    {
        return new WebDavPrincipalValueObject($id, 'Alice');
    }

    public function test_it_resolves_a_configured_space(): void
    {
        config()->set('webdav-server.storage.spaces.default', [
            'disk' => 'local',
            'root' => 'webdav',
            'prefix' => '/',
        ]);

        $result = $this->makeResolver()->resolve($this->setPrincipal('42'), 'default');

        $this->assertInstanceOf(WebDavStorageSpaceValueObject::class, $result);
        $this->assertSame('local', $result->disk);
        $this->assertSame('webdav/42', $result->rootPath);
    }

    public function test_it_appends_a_non_root_prefix_between_root_and_principal_id(): void
    {
        config()->set('webdav-server.storage.spaces.team', [
            'disk' => 'local',
            'root' => 'files',
            'prefix' => 'users',
        ]);

        $result = $this->makeResolver()->resolve($this->setPrincipal('7'), 'team');

        $this->assertSame('files/users/7', $result->rootPath);
    }

    public function test_it_skips_root_level_prefix(): void
    {
        config()->set('webdav-server.storage.spaces.default', [
            'disk' => 'local',
            'root' => 'webdav',
            'prefix' => '/',
        ]);

        $result = $this->makeResolver()->resolve($this->setPrincipal('5'), 'default');

        $this->assertSame('webdav/5', $result->rootPath);
    }

    public function test_it_throws_when_space_is_not_configured(): void
    {
        $this->expectException(SpaceNotConfiguredException::class);
        $this->expectExceptionMessage('"missing"');

        config()->set('webdav-server.storage.spaces', []);

        $this->makeResolver()->resolve($this->setPrincipal(), 'missing');
    }

    public function test_it_throws_when_disk_is_missing_from_space_config(): void
    {
        $this->expectException(InvalidSpaceConfigurationException::class);

        config()->set('webdav-server.storage.spaces.bad', [
            'root' => 'webdav',
        ]);

        $this->makeResolver()->resolve($this->setPrincipal(), 'bad');
    }

    public function test_it_throws_when_root_is_missing_from_space_config(): void
    {
        $this->expectException(InvalidSpaceConfigurationException::class);

        config()->set('webdav-server.storage.spaces.bad', [
            'disk' => 'local',
        ]);

        $this->makeResolver()->resolve($this->setPrincipal(), 'bad');
    }

    public function test_it_uses_the_principal_id_as_the_final_path_segment(): void
    {
        config()->set('webdav-server.storage.spaces.default', [
            'disk' => 'local',
            'root' => 'webdav',
            'prefix' => '/',
        ]);

        $result = $this->makeResolver()->resolve(new WebDavPrincipalValueObject('user-xyz', 'X'), 'default');

        $this->assertStringEndsWith('/user-xyz', $result->rootPath);
    }
}
