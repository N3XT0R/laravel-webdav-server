<?php

declare(strict_types=1);

namespace N3XT0R\LaravelWebdavServer\Tests\Integration\Services;

use Illuminate\Contracts\Config\Repository;
use N3XT0R\LaravelWebdavServer\Exception\Storage\InvalidSpaceConfigurationException;
use N3XT0R\LaravelWebdavServer\Exception\Storage\SpaceNotConfiguredException;
use N3XT0R\LaravelWebdavServer\Services\PathResolverService;
use N3XT0R\LaravelWebdavServer\Tests\TestCase;
use N3XT0R\LaravelWebdavServer\ValueObjects\WebDavPrincipalValueObject;

final class PathResolverServiceTest extends TestCase
{
    private function makeService(): PathResolverService
    {
        return $this->app->make(PathResolverService::class);
    }

    private function principal(string $id = '42'): WebDavPrincipalValueObject
    {
        return new WebDavPrincipalValueObject(id: $id, displayName: 'Test User');
    }

    private function setSpaceConfig(array $spaces): void
    {
        $this->app->make(Repository::class)->set('webdav-server.storage.spaces', $spaces);
    }

    // --- resolvePath ---

    public function test_resolve_path_returns_root_and_principal_id_when_no_prefix(): void
    {
        $this->setSpaceConfig(['files' => ['disk' => 'local', 'root' => 'webdav']]);

        $path = $this->makeService()->resolvePath($this->principal('42'), 'files');

        $this->assertSame('webdav/42', $path);
    }

    public function test_resolve_path_includes_prefix_between_root_and_principal_id(): void
    {
        $this->setSpaceConfig(['team' => ['disk' => 'local', 'root' => 'webdav', 'prefix' => 'team-a']]);

        $path = $this->makeService()->resolvePath($this->principal('7'), 'team');

        $this->assertSame('webdav/team-a/7', $path);
    }

    public function test_resolve_path_trims_slashes_from_root_and_prefix(): void
    {
        $this->setSpaceConfig(['trimmed' => ['disk' => 'local', 'root' => '/webdav/', 'prefix' => '/sub/']]);

        $path = $this->makeService()->resolvePath($this->principal('5'), 'trimmed');

        $this->assertSame('webdav/sub/5', $path);
    }

    public function test_resolve_path_ignores_slash_only_prefix(): void
    {
        $this->setSpaceConfig(['slash' => ['disk' => 'local', 'root' => 'webdav', 'prefix' => '/']]);

        $path = $this->makeService()->resolvePath($this->principal('9'), 'slash');

        $this->assertSame('webdav/9', $path);
    }

    public function test_resolve_path_throws_when_space_key_not_configured(): void
    {
        $this->expectException(SpaceNotConfiguredException::class);

        $this->makeService()->resolvePath($this->principal(), 'unknown');
    }

    public function test_resolve_path_throws_when_root_is_missing(): void
    {
        $this->setSpaceConfig(['bad' => ['disk' => 'local']]);

        $this->expectException(InvalidSpaceConfigurationException::class);

        $this->makeService()->resolvePath($this->principal(), 'bad');
    }

    public function test_resolve_path_throws_when_root_is_empty_string(): void
    {
        $this->setSpaceConfig(['bad' => ['disk' => 'local', 'root' => '   ']]);

        $this->expectException(InvalidSpaceConfigurationException::class);

        $this->makeService()->resolvePath($this->principal(), 'bad');
    }

    // --- resolveUrl ---

    public function test_resolve_url_returns_mount_url_for_space_key(): void
    {
        $this->app->make(Repository::class)->set('app.url', 'https://app.test');
        $this->app->make(Repository::class)->set('webdav-server.route_prefix', 'webdav');

        $url = $this->makeService()->resolveUrl('default');

        $this->assertSame('https://app.test/webdav/default', $url);
    }

    public function test_resolve_url_trims_trailing_slash_from_app_url(): void
    {
        $this->app->make(Repository::class)->set('app.url', 'https://app.test/');
        $this->app->make(Repository::class)->set('webdav-server.route_prefix', 'webdav');

        $url = $this->makeService()->resolveUrl('files');

        $this->assertSame('https://app.test/webdav/files', $url);
    }
}
